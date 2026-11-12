<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\Package;
use App\Models\StaffUser;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_upload_is_private_and_only_supported_images_are_accepted(): void
    {
        Storage::fake('local');
        [$inspection, $staff] = $this->activeInspection();

        $this->actingAs($staff)->post(route('staff.inspections.photos.store', $inspection), [
            'photos' => [UploadedFile::fake()->image('evidence.png', 640, 480)],
            'captions' => ['Front wing condition'],
        ])->assertRedirect();

        $photo = $inspection->photos()->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($photo->storage_key));
        $this->assertSame('Front wing condition', $photo->caption);
        $this->actingAs($staff)->get(route('staff.inspection-photos.show', $photo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put(route('staff.inspection-photos.update', $photo), ['caption' => 'Revised front wing view'])->assertRedirect();
        $this->assertSame('Revised front wing view', $photo->fresh()->caption);
        $this->putJson(route('staff.inspection-photos.update', $photo), ['caption' => 'Auto-saved caption'])
            ->assertOk()
            ->assertJsonPath('caption', 'Auto-saved caption');
        $this->actingAs($staff)->get(route('staff.inspections.show', $inspection))
            ->assertOk()
            ->assertSee('data-caption-edit', false)
            ->assertSee('data-caption-input', false)
            ->assertDontSee('Save caption')
            ->assertDontSee('onsubmit="return confirm', false)
            ->assertDontSee('Remove this inspection photo');

        $inspectionUrl = route('staff.inspections.show', ['inspection' => $inspection, 'section' => 18]);
        $this->actingAs($staff)->from($inspectionUrl)
            ->delete(route('staff.inspection-photos.destroy', $photo))
            ->assertRedirect($inspectionUrl);
        $this->assertFalse(Storage::disk('local')->exists($photo->storage_key));

        $this->actingAs($staff)->post(route('staff.inspections.photos.store', $inspection), [
            'photos' => [UploadedFile::fake()->create('not-an-image.txt', 12, 'text/plain')],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_video_url_is_normalized_and_rejected_for_a_non_video_package(): void
    {
        [$inspection, $staff] = $this->activeInspection(videoIncluded: true, packageCode: 'gold');
        InspectionCheck::query()->create([
            'inspection_id' => $inspection->id,
            'section_title_snapshot' => 'Engine',
            'question_text_snapshot' => 'Check engine condition',
            'sort_order' => 1,
            'rating_code' => 'good',
        ]);
        $this->actingAs($staff)->get(route('staff.inspections.show', $inspection))
            ->assertOk()
            ->assertSee('Inspection video (Gold package)');

        $this->actingAs($staff)->put(route('staff.inspections.video.store', $inspection), [
            'youtube_url' => 'https://youtu.be/abcdefghijk?t=20',
        ])->assertRedirect();
        $this->assertDatabaseHas('inspection_videos', [
            'inspection_id' => $inspection->id,
            'video_id' => 'abcdefghijk',
            'source_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $this->put(route('staff.inspections.video.store', $inspection), ['youtube_url' => 'https://example.com/watch?v=abcdefghijk'])->assertSessionHasErrors('youtube_url');
    }

    public function test_video_is_forbidden_on_non_gold_packages(): void
    {
        [$inspection, $staff] = $this->activeInspection(videoIncluded: true, packageCode: 'silver');
        InspectionCheck::query()->create([
            'inspection_id' => $inspection->id,
            'section_title_snapshot' => 'Engine',
            'question_text_snapshot' => 'Check engine condition',
            'sort_order' => 1,
            'rating_code' => 'good',
        ]);
        $this->actingAs($staff)->get(route('staff.inspections.show', $inspection))
            ->assertOk()
            ->assertDontSee('Inspection video (Gold package)');

        $this->actingAs($staff)->put(route('staff.inspections.video.store', $inspection), [
            'youtube_url' => 'https://youtu.be/abcdefghijk',
        ])->assertForbidden();
        $this->assertDatabaseCount('inspection_videos', 0);
    }

    private function activeInspection(bool $videoIncluded = false, string $packageCode = 'silver'): array
    {
        $customer = Customer::query()->create(['full_name' => 'Evidence Customer', 'email' => 'evidence@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'EF12 GHI']);
        $package = Package::query()->create(['code' => $packageCode, 'name' => ucfirst($packageCode).' package', 'base_price' => '1.00', 'currency' => 'GBP', 'active' => true, 'video_included' => $videoIncluded]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-EVIDENCE', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'staff', 'status' => 'in_progress', 'quoted_total' => '1.00', 'currency' => 'GBP',
        ]);
        $inspection = Inspection::query()->create([
            'booking_id' => $booking->id, 'status' => 'in_progress', 'started_at' => now(),
            'package_snapshot' => ['code' => $packageCode, 'name' => ucfirst($packageCode).' package', 'video_included' => $videoIncluded],
            'feature_snapshot' => [], 'rating_scheme_snapshot' => [],
        ]);
        $staff = StaffUser::query()->create([
            'email' => 'evidence-staff@example.test', 'display_name' => 'Evidence Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);

        return [$inspection, $staff];
    }
}
