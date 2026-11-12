<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\Package;
use App\Models\StaffUser;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_redirects_guests_to_sign_in(): void
    {
        $this->get(route('staff.bookings.index'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_inactive_staff_cannot_sign_in(): void
    {
        StaffUser::query()->create([
            'email' => 'inactive@example.test', 'display_name' => 'Inactive Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => false,
        ]);

        $this->post(route('staff.login.store'), [
            'email' => 'inactive@example.test', 'password' => 'correct horse battery staple',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_staff_can_open_booking_dashboard(): void
    {
        $staff = StaffUser::query()->create([
            'email' => 'staff@example.test', 'display_name' => 'Cerberus Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);

        $this->actingAs($staff)->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee('STAFF')
            ->assertSee('placeholder="Search ref, name, email or reg"', false)
            ->assertSee('aria-label="Search by reference, customer name, email or registration"', false)
            ->assertSee('data-wide-placeholder="Search by reference, customer name, email or registration"', false)
            ->assertSee('ResizeObserver', false);
    }

    public function test_booking_dashboard_shows_appointment_metrics(): void
    {
        $staff = StaffUser::query()->create(['email' => 'staff@example.test', 'display_name' => 'Cerberus Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $customer = Customer::query()->create(['full_name' => 'Metric Customer', 'email' => 'metric@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'ME12 TRC']);
        $package = Package::query()->create(['code' => 'metric-package', 'name' => 'Metric Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true]);
        Booking::query()->create(['booking_reference' => 'CER-METRIC', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'confirmed', 'appointment_at' => now()->addHour(), 'quoted_total' => 10, 'currency' => 'GBP']);

        $this->actingAs($staff)->get(route('staff.bookings.index'))->assertOk()->assertSee('Appointments today')->assertSee('Next 7 days')->assertSee('CER-METRIC');
    }

    public function test_booking_dashboard_filters_by_status(): void
    {
        $staff = StaffUser::query()->create(['email' => 'staff@example.test', 'display_name' => 'Cerberus Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $customer = Customer::query()->create(['full_name' => 'Status Customer', 'email' => 'status@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'STAT01']);
        $package = Package::query()->create(['code' => 'status-package', 'name' => 'Status Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true]);
        Booking::query()->create(['booking_reference' => 'CER-STATUS-CONFIRMED', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'confirmed', 'quoted_total' => 10, 'currency' => 'GBP']);
        Booking::query()->create(['booking_reference' => 'CER-STATUS-REQUESTED', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'requested', 'quoted_total' => 10, 'currency' => 'GBP']);

        $this->actingAs($staff)->get(route('staff.bookings.index', ['status' => 'confirmed']))
            ->assertOk()->assertSee('CER-STATUS-CONFIRMED')->assertDontSee('CER-STATUS-REQUESTED');
    }

    public function test_booking_dashboard_shows_cancelled_bookings(): void
    {
        $staff = StaffUser::query()->create(['email' => 'staff@example.test', 'display_name' => 'Cerberus Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $customer = Customer::query()->create(['full_name' => 'Cancelled Dashboard Customer', 'email' => 'cancelled-dashboard@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'CANCEL01']);
        $package = Package::query()->create(['code' => 'cancelled-dashboard-package', 'name' => 'Cancelled Dashboard Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true]);
        Booking::query()->create(['booking_reference' => 'CER-CANCELLED-HIDDEN', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'cancelled', 'quoted_total' => 10, 'currency' => 'GBP']);
        Booking::query()->create(['booking_reference' => 'CER-REQUESTED-VISIBLE', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'requested', 'quoted_total' => 10, 'currency' => 'GBP']);

        $this->actingAs($staff)->get(route('staff.bookings.index'))
            ->assertOk()->assertSee('CER-REQUESTED-VISIBLE')->assertSee('CER-CANCELLED-HIDDEN')
            ->assertSee('Cancelled')
            ->assertSee(route('staff.bookings.index', ['status' => 'cancelled']), false);
    }

    public function test_clicking_a_completed_booking_with_inspection_opens_its_checklist(): void
    {
        $staff = StaffUser::query()->create(['email' => 'completed-link-staff@example.test', 'display_name' => 'Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $customer = Customer::query()->create(['full_name' => 'Completed Link Customer', 'email' => 'completed-link@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'DONE123']);
        $package = Package::query()->create(['code' => 'completed-link-package', 'name' => 'Completed Link Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-COMPLETED-LINK', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'status' => 'completed', 'quoted_total' => 10, 'currency' => 'GBP',
        ]);
        $inspection = Inspection::query()->create([
            'booking_id' => $booking->id, 'status' => 'completed', 'started_at' => now()->subHour(), 'completed_at' => now(),
            'package_snapshot' => ['code' => 'completed-link-package', 'name' => 'Completed Link Package'],
            'feature_snapshot' => [], 'rating_scheme_snapshot' => [],
        ]);

        $this->actingAs($staff)->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee('href="'.route('staff.inspections.show', $inspection).'"', false)
            ->assertDontSee('href="'.route('staff.bookings.show', $booking).'"', false);
    }

    public function test_staff_can_export_filtered_bookings_as_csv_without_formula_injection(): void
    {
        $staff = StaffUser::query()->create(['email' => 'staff@example.test', 'display_name' => 'Cerberus Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $customer = Customer::query()->create(['full_name' => '=Injected Name', 'email' => 'csv@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'CSV123']);
        $package = Package::query()->create(['code' => 'csv-package', 'name' => 'CSV Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true]);
        Booking::query()->create(['booking_reference' => 'CER-CSV', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'requested', 'quoted_total' => 10, 'currency' => 'GBP']);

        $response = $this->actingAs($staff)->get(route('staff.bookings.export', ['status' => 'requested']));
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=cerberus-bookings-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_booking_dashboard_filters_by_appointment_date_range(): void
    {
        $staff = StaffUser::query()->create(['email' => 'staff@example.test', 'display_name' => 'Cerberus Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $customer = Customer::query()->create(['full_name' => 'Date Customer', 'email' => 'date@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'DATE01']);
        $package = Package::query()->create(['code' => 'date-package', 'name' => 'Date Package', 'base_price' => 10, 'currency' => 'GBP', 'active' => true]);
        Booking::query()->create(['booking_reference' => 'CER-DATE-IN', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'confirmed', 'appointment_at' => now()->addDays(2), 'quoted_total' => 10, 'currency' => 'GBP']);
        Booking::query()->create(['booking_reference' => 'CER-DATE-OUT', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'package_id' => $package->id, 'status' => 'confirmed', 'appointment_at' => now()->addDays(10), 'quoted_total' => 10, 'currency' => 'GBP']);

        $this->actingAs($staff)->get(route('staff.bookings.index', ['appointment_from' => now()->addDay()->toDateString(), 'appointment_to' => now()->addDays(3)->toDateString()]))
            ->assertOk()->assertSee('CER-DATE-IN')->assertDontSee('CER-DATE-OUT');
    }

    public function test_deactivated_staff_is_logged_out_on_the_next_staff_request(): void
    {
        $staff = StaffUser::query()->create([
            'email' => 'deactivated@example.test', 'display_name' => 'Deactivated Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => false,
        ]);

        $this->actingAs($staff)->get(route('staff.bookings.index'))->assertRedirect(route('staff.login'));
        $this->assertGuest();
    }
}
