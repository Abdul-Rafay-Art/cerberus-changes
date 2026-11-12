<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\InspectionFeature;
use App\Models\RatingOption;
use App\Services\NotificationDeliveryService;
use App\Services\ReportGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class InspectionController
{
    public function __construct(
        private ReportGenerationService $reportGenerator,
        private NotificationDeliveryService $notifications,
    ) {}

    public function start(Booking $booking): RedirectResponse
    {
        if ($booking->inspection()->exists()) {
            return redirect()->route('staff.inspections.show', $booking->inspection()->value('id'));
        }
        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['inspection' => 'Confirm the appointment before starting an inspection.']);
        }

        $booking->load(['package.features', 'addons']);
        $includedFeatureIds = collect($booking->package->features->where('pivot.included', true)->modelKeys());
        $addons = $booking->addons->keyBy('id');
        $addonIds = $addons->keys();
        $featureIds = $includedFeatureIds->merge($addonIds)->unique()->values();
        $features = InspectionFeature::query()->whereIn('id', $featureIds)->with([
            'questions' => fn ($query) => $query->where('questions.active', true)->whereHas('section', fn ($section) => $section->where('active', true)),
            'questions.section',
        ])->get();
        $questions = $features->flatMap(fn (InspectionFeature $feature) => $feature->questions)
            ->unique('id')
            ->sortBy(fn ($question) => sprintf('%06d.%06d', $question->section->sort_order, $question->sort_order))
            ->values();

        if ($questions->isEmpty()) {
            return back()->withErrors(['inspection' => 'This package does not yet have an approved checklist configured.']);
        }

        $packageSnapshot = [
            'id' => $booking->package->id,
            'code' => $booking->package->code,
            'name' => $booking->package->name,
            'base_price' => $booking->package->base_price,
            'currency' => $booking->package->currency,
            'quoted_total' => $booking->quoted_total,
            'video_included' => $booking->package->videoEnabled(),
            'is_demo' => $booking->package->is_demo,
        ];
        $featureSnapshot = $features->map(function (InspectionFeature $feature) use ($addons): array {
            $addon = $addons->get($feature->id);

            return [
                'id' => $feature->id,
                'code' => $feature->code,
                'name' => $feature->name,
                'selection_type' => $addon ? 'addon' : 'included',
                'price' => $addon ? number_format((float) $addon->pivot->price_snapshot, 2, '.', '') : null,
            ];
        })->values()->all();

        $inspection = DB::transaction(function () use ($booking, $questions, $packageSnapshot, $featureSnapshot): Inspection {
            $inspection = Inspection::query()->create([
                'booking_id' => $booking->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'package_snapshot' => $packageSnapshot,
                'feature_snapshot' => $featureSnapshot,
                'rating_scheme_snapshot' => RatingOption::activeScheme(),
                'created_by' => request()->user()->id,
            ]);

            foreach ($questions as $index => $question) {
                InspectionCheck::query()->create([
                    'inspection_id' => $inspection->id,
                    'question_id' => $question->id,
                    'section_title_snapshot' => $question->section->title,
                    'question_text_snapshot' => $question->prompt,
                    'sort_order' => $index + 1,
                ]);
            }

            $booking->update(['status' => 'in_progress', 'updated_by' => request()->user()->id]);

            return $inspection;
        });

        return redirect()->route('staff.inspections.show', $inspection);
    }

    public function show(Inspection $inspection): View
    {
        $inspection->load(['booking.customer', 'booking.vehicle', 'booking.package', 'checks', 'photos', 'video', 'reports']);
        $latestReport = $inspection->reports->first();
        $reportNeedsRegeneration = $this->reportGenerator->needsNewVersion($inspection, $latestReport);

        return view('staff.inspections.show', [
            'inspection' => $inspection,
            'ratingScheme' => collect($inspection->rating_scheme_snapshot),
            'sections' => $inspection->checks->groupBy('section_title_snapshot'),
            'canEditChecks' => in_array($inspection->status, ['in_progress', 'completed'], true),
            'reportNeedsRegeneration' => $reportNeedsRegeneration,
        ]);
    }

    public function saveCheck(Request $request, InspectionCheck $check): JsonResponse
    {
        $inspection = $check->inspection;
        $canEdit = in_array($inspection->status, ['in_progress', 'completed'], true);
        abort_unless($canEdit, 409, 'This inspection is no longer editable.');

        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'rating_code' => ['nullable', Rule::in(array_column(RatingOption::activeScheme(), 'code'))],
            'notes' => ['nullable', 'string', 'max:20000'],
            'measurements' => ['nullable', 'string', 'max:5000'],
        ]);

        $updated = InspectionCheck::query()->whereKey($check->id)->where('version', $validated['version'])->update([
            'rating_code' => $validated['rating_code'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'measurements' => $validated['measurements'] ?? null,
            'last_saved_at' => now(),
            'version' => DB::raw('version + 1'),
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            $latest = $check->fresh();

            return response()->json([
                'message' => 'A newer edit was saved. Reload this check before continuing.',
                'version' => $latest->version,
                'rating_code' => $latest->rating_code,
                'notes' => $latest->notes,
                'measurements' => $latest->measurements,
            ], 409);
        }

        $check->refresh();

        return response()->json(['version' => $check->version, 'saved_at' => $check->last_saved_at->toIso8601String()]);
    }

    public function complete(Request $request, Inspection $inspection): RedirectResponse
    {
        $completion = DB::transaction(function () use ($inspection, $request): int|false {
            $lockedInspection = Inspection::query()->whereKey($inspection->id)->lockForUpdate()->firstOrFail();
            if ($lockedInspection->status !== 'in_progress') {
                return false;
            }

            $missing = $lockedInspection->checks()->whereNull('rating_code')->count();
            if ($missing > 0) {
                return $missing;
            }

            $lockedInspection->update(['status' => 'completed', 'completed_at' => now()]);
            $lockedInspection->booking()->update(['status' => 'completed', 'updated_by' => $request->user()->id]);

            return 0;
        });

        if ($completion === false) {
            return back()->withErrors(['inspection' => 'This inspection is already finalized.']);
        }
        if ($completion > 0) {
            return back()->withErrors(['inspection' => $completion.' checklist item(s) still need a rating, including Not Inspected or Not Applicable where appropriate.']);
        }

        try {
            [$report, $plainToken] = $this->reportGenerator->generate($inspection->fresh(), (string) $request->user()->id);
            $emailSent = $this->notifications->report($inspection->booking, route('customer-reports.show', $plainToken));

            if (! $emailSent) {
                $report->accessTokens()->where('token_hash', hash('sha256', $plainToken))->whereNull('revoked_at')->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::error('Automatic inspection report delivery failed.', [
                'inspection_id' => $inspection->id,
                'exception' => $exception::class,
            ]);

            return redirect()->route('staff.inspections.show', $inspection)
                ->with('report_delivery_failed', true)
                ->with('status', 'Inspection completed, but its PDF or customer email could not be prepared. Contact an administrator.');
        }

        if (! $emailSent) {
            return redirect()->route('staff.inspections.show', $inspection)
                ->with('report_delivery_failed', true)
                ->with('status', 'Inspection completed and the PDF was generated, but the customer email could not be sent. Contact an administrator.');
        }

        return redirect()->route('staff.inspections.show', $inspection)
            ->with('inspection_completed', true)
            ->with('inspection_completion_message', 'The PDF was generated and its secure link was emailed to the customer.');
    }
}
