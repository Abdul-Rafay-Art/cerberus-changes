<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\InspectionFeature;
use App\Models\InspectionSection;
use App\Models\Package;
use App\Models\Question;
use App\Models\StaffUser;
use App\Models\Vehicle;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_an_inspection_snapshots_the_package_checklist_and_autosaves_responses(): void
    {
        [$booking, $question] = $this->confirmedBookingWithChecklist();
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->post(route('staff.bookings.start-inspection', $booking));
        $response->assertSessionHasNoErrors();
        $this->assertTrue(Inspection::query()->where('booking_id', $booking->id)->exists(), 'HTTP '.$response->status().' response: '.$response->getContent());
        $inspection = Inspection::query()->where('booking_id', $booking->id)->firstOrFail();
        $check = $inspection->checks()->firstOrFail();

        $response->assertRedirect(route('staff.inspections.show', $inspection));
        $this->assertSame($question->prompt, $check->question_text_snapshot);
        $this->assertSame('5.00', collect($inspection->feature_snapshot)->firstWhere('selection_type', 'addon')['price']);
        $this->assertSame('10.00', $inspection->package_snapshot['quoted_total']);
        $this->assertSame('in_progress', $booking->fresh()->status);

        $this->actingAs($staff)->putJson(route('staff.inspection-checks.update', $check), [
            'version' => 0,
            'rating_code' => 'warning',
            'notes' => "Observed wear\nNeeds monitoring",
            'measurements' => '2.4 mm',
        ])->assertOk()->assertJsonPath('version', 1);

        $this->assertSame('warning', $check->fresh()->rating_code);
        $this->assertSame("Observed wear\nNeeds monitoring", $check->fresh()->notes);
        $this->assertSame('2.4 mm', $check->fresh()->measurements);
        $this->actingAs($staff)->get(route('staff.inspections.show', $inspection))
            ->assertOk()
            ->assertSee('class="check-status " style="--rating-color:#e1c93f"', false)
            ->assertSee("status.style.setProperty('--rating-color',ratingColor||'#879195')", false);
    }

    public function test_stale_autosave_version_is_rejected_instead_of_overwriting_a_newer_response(): void
    {
        [$booking] = $this->confirmedBookingWithChecklist();
        $staff = $this->staffUser();
        $this->actingAs($staff)->post(route('staff.bookings.start-inspection', $booking));
        $check = Inspection::query()->where('booking_id', $booking->id)->firstOrFail()->checks()->firstOrFail();

        $this->actingAs($staff)->putJson(route('staff.inspection-checks.update', $check), [
            'version' => 0, 'rating_code' => 'good', 'notes' => 'First value', 'measurements' => '',
        ])->assertOk();

        $this->putJson(route('staff.inspection-checks.update', $check), [
            'version' => 0, 'rating_code' => 'poor', 'notes' => 'Stale value', 'measurements' => '',
        ])->assertStatus(409)->assertJsonPath('version', 1);

        $this->assertSame('good', $check->fresh()->rating_code);
        $this->assertSame('First value', $check->fresh()->notes);
    }

    public function test_inspection_evidence_section_unlocks_after_all_checklist_items_are_rated(): void
    {
        [$booking] = $this->confirmedBookingWithChecklist();
        $staff = $this->staffUser();
        $this->actingAs($staff)->post(route('staff.bookings.start-inspection', $booking));
        $inspection = Inspection::query()->where('booking_id', $booking->id)->firstOrFail();
        $check = $inspection->checks()->firstOrFail();

        $incompletePage = $this->actingAs($staff)->get(route('staff.inspections.show', $inspection));
        $incompletePage->assertOk()->assertDontSee('inspection-evidence-locked');
        $this->assertMatchesRegularExpression('/<section class="evidence" id="evidence"\s+hidden\s*>/', $incompletePage->getContent());
        $this->assertMatchesRegularExpression('/<form[^>]+id="complete-form"[^>]+hidden\s*>/', $incompletePage->getContent());

        $this->actingAs($staff)->putJson(route('staff.inspection-checks.update', $check), [
            'version' => 0,
            'rating_code' => 'not_inspected',
            'notes' => '',
            'measurements' => '',
        ])->assertOk();

        $completeChecklistPage = $this->actingAs($staff)->get(route('staff.inspections.show', $inspection));
        $completeChecklistPage->assertOk()->assertSee('Inspection photos');
        $this->assertDoesNotMatchRegularExpression('/<section class="evidence" id="evidence"\s+hidden\s*>/', $completeChecklistPage->getContent());
        $this->assertDoesNotMatchRegularExpression('/<form[^>]+id="complete-form"[^>]+hidden\s*>/', $completeChecklistPage->getContent());
    }

    public function test_inspection_cannot_be_completed_until_every_check_has_a_rating(): void
    {
        Storage::fake('local');
        [$booking] = $this->confirmedBookingWithChecklist();
        $staff = $this->staffUser();
        $this->mock(NotificationDeliveryService::class)
            ->shouldReceive('report')->once()->andReturn(true);
        $this->actingAs($staff)->post(route('staff.bookings.start-inspection', $booking));
        $inspection = Inspection::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->actingAs($staff)->post(route('staff.inspections.complete', $inspection))->assertSessionHasErrors('inspection');
        $this->assertSame('in_progress', $inspection->fresh()->status);

        $check = $inspection->checks()->firstOrFail();
        $this->putJson(route('staff.inspection-checks.update', $check), [
            'version' => 0, 'rating_code' => 'not_inspected', 'notes' => '', 'measurements' => '',
        ])->assertOk();
        $this->followingRedirects()->post(route('staff.inspections.complete', $inspection))
            ->assertOk()
            ->assertSee('completion-dialog', false)
            ->assertSee('Inspection completed')
            ->assertSee('The PDF was generated and its secure link was emailed to the customer.')
            ->assertSee('data-close-completion', false)
            ->assertDontSee('Inspection completed. The PDF was generated and its secure link was emailed to the customer.');
        $this->assertSame('completed', $inspection->fresh()->status);
        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseCount('report_access_tokens', 1);
    }

    private function confirmedBookingWithChecklist(): array
    {
        $customer = Customer::query()->create(['full_name' => 'Inspector Customer', 'email' => 'customer@example.test', 'phone' => '07123456789']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'AB12 CDE']);
        $package = Package::query()->create(['code' => 'fixture-package', 'name' => 'Fixture', 'base_price' => '10.00', 'currency' => 'GBP', 'active' => true]);
        $feature = InspectionFeature::query()->create(['code' => 'fixture-feature', 'name' => 'Fixture feature', 'active' => true]);
        $addon = InspectionFeature::query()->create([
            'code' => 'fixture-addon', 'name' => 'Fixture add-on', 'addon_price' => '5.00', 'addon_enabled' => true, 'active' => true,
        ]);
        $section = InspectionSection::query()->create(['code' => 'fixture-section', 'title' => 'Fixture section', 'sort_order' => 1]);
        $question = Question::query()->create(['section_id' => $section->id, 'code' => 'fixture-question', 'prompt' => 'Check the fixture item', 'response_type' => 'rating', 'active' => true]);
        $package->features()->attach($feature->id, ['included' => true, 'sort_order' => 1]);
        $package->features()->attach($addon->id, ['included' => false, 'sort_order' => 2]);
        $feature->questions()->attach($question->id, ['sort_order' => 1]);
        $addon->questions()->attach($question->id, ['sort_order' => 1]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-TEST-INSP', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'staff', 'status' => 'confirmed',
            'confirmed_at' => now(), 'appointment_at' => now()->addDay(), 'quoted_total' => '10.00', 'currency' => 'GBP',
        ]);
        $booking->addons()->attach($addon->id, ['price_snapshot' => '5.00']);

        return [$booking, $question];
    }

    private function staffUser(): StaffUser
    {
        return StaffUser::query()->create([
            'email' => 'inspector@example.test', 'display_name' => 'Inspector',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);
    }
}
