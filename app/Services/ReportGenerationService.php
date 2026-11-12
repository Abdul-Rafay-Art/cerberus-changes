<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Report;
use App\Models\ReportAccessToken;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ReportGenerationService
{
    public function needsNewVersion(Inspection $inspection, ?Report $latestReport): bool
    {
        if (! $latestReport) {
            return true;
        }

        if ($latestReport->inspection_snapshot_hash) {
            return ! hash_equals($latestReport->inspection_snapshot_hash, $this->snapshotHash($inspection));
        }

        $inspection->loadMissing(['checks', 'photos', 'video']);
        $updatedRecords = $inspection->checks
            ->concat($inspection->photos)
            ->when($inspection->video, fn ($records) => $records->push($inspection->video));

        return $updatedRecords->contains(fn ($record): bool => $record->updated_at?->greaterThan($latestReport->generated_at) ?? false);
    }

    public function snapshotHash(Inspection $inspection): string
    {
        $inspection->loadMissing(['checks', 'photos', 'video']);
        $snapshot = [
            'package' => $inspection->package_snapshot,
            'features' => $inspection->feature_snapshot,
            'rating_scheme' => $inspection->rating_scheme_snapshot,
            'completed_at' => $inspection->completed_at?->toIso8601String(),
            'checks' => $inspection->checks->map(fn ($check): array => [
                'question_id' => $check->question_id,
                'section' => $check->section_title_snapshot,
                'question' => $check->question_text_snapshot,
                'sort_order' => $check->sort_order,
                'rating' => $check->rating_code,
                'notes' => $check->notes,
                'measurements' => $check->measurements,
            ])->values()->all(),
            'photos' => $inspection->photos->map(fn ($photo): array => [
                'storage_key' => $photo->storage_key,
                'filename' => $photo->original_filename,
                'caption' => $photo->caption,
                'sort_order' => $photo->sort_order,
            ])->values()->all(),
            'video' => $inspection->video ? [
                'provider' => $inspection->video->provider,
                'source_url' => $inspection->video->source_url,
                'video_id' => $inspection->video->video_id,
            ] : null,
        ];

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return array{0: Report, 1: string} */
    public function generate(Inspection $inspection, string $generatedBy): array
    {
        $inspection->load(['booking.customer', 'booking.vehicle', 'booking.package', 'checks', 'photos', 'video']);
        $photos = $inspection->photos->map(function ($photo) {
            $contents = Storage::disk('local')->get($photo->storage_key);
            $source = imagecreatefromstring($contents);
            if ($source === false) {
                throw new RuntimeException('An uploaded inspection photo could not be decoded for the report.');
            }

            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $scale = min(1, 1800 / max($sourceWidth, $sourceHeight));
            $targetWidth = max(1, (int) round($sourceWidth * $scale));
            $targetHeight = max(1, (int) round($sourceHeight * $scale));
            $optimized = imagecreatetruecolor($targetWidth, $targetHeight);
            $white = imagecolorallocate($optimized, 255, 255, 255);
            imagefill($optimized, 0, 0, $white);
            imagecopyresampled($optimized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            ob_start();
            imagejpeg($optimized, null, 82);
            $optimizedBytes = (string) ob_get_clean();
            imagedestroy($source);
            imagedestroy($optimized);
            $photo->inline_data = 'data:image/jpeg;base64,'.base64_encode($optimizedBytes);

            return $photo;
        });

        $snapshotHash = $this->snapshotHash($inspection);

        return DB::transaction(function () use ($inspection, $photos, $generatedBy, $snapshotHash): array {
            Inspection::query()->whereKey($inspection->id)->lockForUpdate()->firstOrFail();
            $version = ((int) Report::query()->where('inspection_id', $inspection->id)->lockForUpdate()->max('version')) + 1;
            $storageKey = 'reports/'.$inspection->id.'/report-v'.$version.'.pdf';
            $logoPath = public_path('images/cerberus-logo.jpg');
            $logoData = is_file($logoPath) ? 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($logoPath)) : null;
            $pdf = Pdf::loadView('reports.pdf', ['inspection' => $inspection, 'photos' => $photos, 'version' => $version, 'logoData' => $logoData])
                ->setPaper('a4')
                ->setOption(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isHtml5ParserEnabled' => true]);
            $pdf->getDomPDF()->setCallbacks([[
                'event' => 'end_document',
                'f' => static function (int $pageNumber, int $pageCount, $canvas, $fontMetrics): void {
                    $text = "Page {$pageNumber} of {$pageCount}";
                    $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
                    $fontSize = 7;
                    $rightMargin = 45.35;
                    $x = $canvas->get_width() - $rightMargin - $fontMetrics->getTextWidth($text, $font, $fontSize);
                    $canvas->text($x, $canvas->get_height() - 22, $text, $font, $fontSize, [104, 113, 118]);
                },
            ]]);

            if (! Storage::disk('local')->put($storageKey, $pdf->output())) {
                throw new RuntimeException('The generated PDF could not be saved to private storage.');
            }

            try {
                $report = Report::query()->create([
                    'inspection_id' => $inspection->id,
                    'storage_key' => $storageKey,
                    'version' => $version,
                    'generated_at' => now(),
                    'inspection_completed_at' => $inspection->completed_at,
                    'inspection_snapshot_hash' => $snapshotHash,
                    'generated_by' => $generatedBy,
                ]);
                $plainToken = Str::random(64);
                ReportAccessToken::query()->create([
                    'report_id' => $report->id,
                    'token_hash' => hash('sha256', $plainToken),
                ]);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($storageKey);
                throw $exception;
            }

            return [$report, $plainToken];
        });
    }
}
