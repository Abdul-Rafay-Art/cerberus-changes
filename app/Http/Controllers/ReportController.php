<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Report;
use App\Models\ReportAccessToken;
use App\Services\NotificationDeliveryService;
use App\Services\ReportGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReportController
{
    public function __construct(
        private ReportGenerationService $reportGenerator,
        private NotificationDeliveryService $notifications,
    ) {}

    public function generateVersion(Request $request, Inspection $inspection): RedirectResponse
    {
        abort_unless($inspection->status === 'completed', 409, 'Only completed inspections can generate another report version.');

        $latestReport = $inspection->reports()->orderByDesc('version')->first();
        if (! $this->reportGenerator->needsNewVersion($inspection, $latestReport)) {
            return redirect()->route('staff.inspections.show', $inspection)
                ->with('status', 'Save a correction to the inspection before generating another report version.');
        }

        try {
            [$report, $plainToken] = $this->reportGenerator->generate($inspection, (string) $request->user()->id);
            $customerUrl = route('customer-reports.show', $plainToken);
            $sent = $this->notifications->report($inspection->booking, $customerUrl);

            if (! $sent) {
                $report->accessTokens()->where('token_hash', hash('sha256', $plainToken))->whereNull('revoked_at')->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

                return redirect()->route('staff.inspections.show', $inspection)
                    ->with('report_delivery_failed', true)
                    ->with('status', 'Report version '.$report->version.' was generated, but its customer email could not be sent. The new link was revoked.');
            }

            return redirect()->to($customerUrl);
        } catch (Throwable $exception) {
            Log::error('Authorized report version generation failed.', [
                'inspection_id' => $inspection->id,
                'actor_id' => $request->user()->id,
                'exception' => $exception::class,
            ]);

            return redirect()->route('staff.inspections.show', $inspection)
                ->with('report_delivery_failed', true)
                ->with('status', 'A new report version could not be generated. Contact an administrator.');
        }
    }

    public function revoke(Request $request, Report $report): RedirectResponse
    {
        $report->accessTokens()->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);

        return redirect()->route('staff.inspections.show', $report->inspection_id)->with('status', 'Active customer report links for version '.$report->version.' have been revoked.');
    }

    public function show(string $token): Response|View
    {
        $access = $this->findValidToken($token);
        if (! $access) {
            return response()->view('reports.unavailable', status: 404)->withHeaders([
                'Cache-Control' => 'private, no-store, max-age=0',
                'Referrer-Policy' => 'no-referrer',
            ]);
        }
        $report = $access->report()->with([
            'inspection.booking.vehicle',
            'inspection.booking.package',
            'inspection.video',
            'inspection.checks',
        ])->firstOrFail();
        $ratingCounts = collect($report->inspection->rating_scheme_snapshot)->mapWithKeys(fn (array $rating) => [
            $rating['code'] => [
                'label' => $rating['label'],
                'color' => $rating['color'],
                'count' => $report->inspection->checks->where('rating_code', $rating['code'])->count(),
            ],
        ]);

        $response = response()->view('reports.customer', ['report' => $report, 'token' => $token, 'ratingCounts' => $ratingCounts]);

        return $response->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-src https://www.youtube-nocookie.com; object-src 'none'; base-uri 'none'; frame-ancestors 'none'",
        ]);
    }

    public function customer(Request $request, Report $report): RedirectResponse
    {
        $plainToken = Str::random(64);
        $access = $report->accessTokens()->create(['token_hash' => hash('sha256', $plainToken)]);
        $customerUrl = route('customer-reports.show', $plainToken);

        try {
            $sent = $this->notifications->report($report->inspection->booking, $customerUrl);
        } catch (Throwable $exception) {
            Log::error('Show report customer email failed.', [
                'report_id' => $report->id,
                'actor_id' => $request->user()->id,
                'exception' => $exception::class,
            ]);
            $sent = false;
        }

        if (! $sent) {
            $access->forceFill(['revoked_at' => now()])->save();

            return redirect()->route('staff.inspections.show', $report->inspection_id)
                ->with('status', 'The customer report link could not be emailed. The unsent link was revoked.');
        }

        return redirect()->to($customerUrl);
    }

    public function download(string $token): Response
    {
        $access = $this->resolveToken($token);
        $report = $access->report;
        abort_unless(Storage::disk('local')->exists($report->storage_key), 404);

        return Storage::disk('local')->download($report->storage_key, 'cerberus-inspection-'.$report->inspection_id.'-v'.$report->version.'.pdf', [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview(Report $report): Response
    {
        abort_unless(Storage::disk('local')->exists($report->storage_key), 404);

        $response = response()->file(Storage::disk('local')->path($report->storage_key), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cerberus-inspection-'.$report->inspection_id.'-v'.$report->version.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    private function resolveToken(string $token): ReportAccessToken
    {
        $access = $this->findValidToken($token);
        abort_unless($access, 404);

        return $access;
    }

    private function findValidToken(string $token): ?ReportAccessToken
    {
        if (preg_match('/^[A-Za-z0-9]{64}$/', $token) !== 1) {
            return null;
        }

        $access = ReportAccessToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
        if (! $access) {
            return null;
        }
        $access->forceFill(['last_accessed_at' => now()])->save();

        return $access;
    }
}
