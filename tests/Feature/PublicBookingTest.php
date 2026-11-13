<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\InspectionFeature;
use App\Models\Package;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_request_stores_multiple_preferences_and_server_calculated_addon_total(): void
    {
        $package = $this->packageWithAddon();
        $this->mock(NotificationDeliveryService::class)
            ->shouldReceive('booking')->once()->withArgs(fn ($booking, $event) => $event === 'requested')->andReturn(true);

        $response = $this->submitBooking($package, ['addons' => [$package->eligibleAddons()->first()->id]]);

        $booking = Booking::query()->firstOrFail();
        $response->assertRedirect(route('bookings.received', $booking->booking_reference));
        $this->assertSame('requested', $booking->status);
        $this->assertSame('117.50', $booking->quoted_total);
        $this->assertDatabaseCount('booking_preferences', 2);
        $this->assertDatabaseHas('booking_addons', ['booking_id' => $booking->id, 'price_snapshot' => '17.50']);
    }

    public function test_repeated_submission_key_does_not_create_a_second_booking(): void
    {
        $package = $this->packageWithAddon();
        $this->mock(NotificationDeliveryService::class)
            ->shouldReceive('booking')->once()->andReturn(true);

        $data = $this->validPayload($package);
        $this->post(route('bookings.store'), $data)->assertRedirect();
        $this->post(route('bookings.store'), $data)->assertRedirect();

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('vehicles', 1);
        $this->assertDatabaseCount('booking_preferences', 2);
    }

    public function test_customer_cannot_select_an_addon_not_eligible_for_the_package(): void
    {
        $package = $this->packageWithAddon();
        $invalidAddon = InspectionFeature::query()->create([
            'code' => 'unavailable-addon', 'name' => 'Unavailable', 'addon_price' => '5.00',
            'addon_enabled' => true, 'active' => true, 'sort_order' => 2,
        ]);
        $response = $this->submitBooking($package, ['addons' => [$invalidAddon->id]]);

        $response->assertSessionHasErrors('addons');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_customer_can_submit_a_hyphenated_vehicle_registration(): void
    {
        $package = $this->packageWithAddon();
        $this->mock(NotificationDeliveryService::class)->shouldReceive('booking')->once()->andReturn(true);

        $this->submitBooking($package, ['registration' => 'KHI-7335'])->assertRedirect();
        $this->assertDatabaseHas('vehicles', ['registration' => 'KHI-7335', 'registration_normalized' => 'KHI7335']);
    }

    public function test_vehicle_make_and_model_are_required(): void
    {
        $package = $this->packageWithAddon();

        $this->submitBooking($package, ['make' => '', 'model' => 'Corolla'])
            ->assertSessionHasErrors('make');
        $this->submitBooking($package, ['make' => 'Toyota', 'model' => ''])
            ->assertSessionHasErrors('model');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_only_the_first_appointment_preference_is_required(): void
    {
        $package = $this->packageWithAddon();
        $this->mock(NotificationDeliveryService::class)->shouldReceive('booking')->once()->andReturn(true);

        $this->submitBooking($package, ['preferences' => [now()->addDay()->format('Y-m-d\\TH:i'), '', '']])->assertRedirect();
        $this->assertDatabaseCount('booking_preferences', 1);
    }

    public function test_package_catalogue_and_quote_endpoints_expose_only_active_eligible_options(): void
    {
        $package = $this->packageWithAddon();
        $addon = $package->eligibleAddons()->firstOrFail();

        $this->getJson(route('api.packages.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $package->id)
            ->assertJsonPath('data.0.eligible_addons.0.id', $addon->id);

        $this->postJson(route('api.packages.quote'), ['package_id' => $package->id, 'addons' => [$addon->id]])
            ->assertOk()
            ->assertJsonPath('total', '117.50')
            ->assertJsonPath('currency', 'GBP');
    }

    private function packageWithAddon(): Package
    {
        $package = Package::query()->create([
            'code' => 'test-package', 'name' => 'Test Package', 'description' => 'Fixture package',
            'base_price' => '100.00', 'currency' => 'GBP', 'active' => true, 'sort_order' => 1,
        ]);
        $addon = InspectionFeature::query()->create([
            'code' => 'test-addon', 'name' => 'Test add-on', 'addon_price' => '17.50',
            'addon_enabled' => true, 'active' => true, 'sort_order' => 1,
        ]);
        $package->features()->attach($addon->id, ['included' => false, 'sort_order' => 1]);

        return $package;
    }

    private function submitBooking(Package $package, array $overrides = []): TestResponse
    {
        return $this->post(route('bookings.store'), array_merge($this->validPayload($package), $overrides));
    }

    private function validPayload(Package $package): array
    {
        return [
            'submission_key' => (string) Str::uuid(),
            'full_name' => 'Alex Customer',
            'email' => 'alex@example.test',
            'phone' => '07123456789',
            'registration' => 'AB12 CDE',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'package_id' => $package->id,
            'preferences' => [now()->addDays(3)->format('Y-m-d H:i:s'), now()->addDays(4)->format('Y-m-d H:i:s')],
            'consent' => '1',
        ];
    }
}
