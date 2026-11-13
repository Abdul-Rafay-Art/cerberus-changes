<?php

namespace Tests\Feature;

use App\Models\InspectionFeature;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_entry_points_render_without_server_errors(): void
    {
        Package::query()->create(['code' => 'smoke-package', 'name' => 'Smoke Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true, 'sort_order' => 1]);

        $this->get(route('home'))->assertRedirect(route('bookings.create'));
        $this->get(route('bookings.create'))->assertOk()->assertSee('Tell us about the vehicle');
        $this->get('/up')->assertOk();
    }

    public function test_public_booking_and_staff_login_forms_expose_expected_controls(): void
    {
        $package = Package::query()->create(['code' => 'smoke-package', 'name' => 'Smoke Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true, 'sort_order' => 1]);
        $feature = InspectionFeature::query()->create(['code' => 'smoke-feature', 'name' => 'Smoke feature', 'active' => true, 'addon_enabled' => false, 'sort_order' => 1]);
        $package->features()->attach($feature->id, ['included' => true, 'sort_order' => 1]);

        $bookingForm = $this->get(route('bookings.create'));
        $bookingForm->assertOk()
            ->assertSee('name="full_name"', false)
            ->assertSee('name="package_id"', false)
            ->assertSee($package->name)
            ->assertDontSee('Make (optional)')
            ->assertDontSee('Model (optional)');
        $this->assertMatchesRegularExpression('/<input id="make" name="make"[^>]*required/', $bookingForm->getContent());
        $this->assertMatchesRegularExpression('/<input id="model" name="model"[^>]*required/', $bookingForm->getContent());
        $this->get(route('staff.login'))->assertOk()->assertSee('name="email"', false)->assertSee('name="password"', false);
    }
}
