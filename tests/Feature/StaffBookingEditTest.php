<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPreference;
use App\Models\Customer;
use App\Models\InspectionFeature;
use App\Models\Package;
use App\Models\StaffUser;
use App\Models\Vehicle;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffBookingEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_edit_customer_vehicle_and_reprice_booking_from_approved_package_records(): void
    {
        $customer = Customer::query()->create(['full_name' => 'Old Customer', 'email' => 'old@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'AB12 CDE']);
        $package = Package::query()->create(['code' => 'edit-package', 'name' => 'Edit Package', 'base_price' => '100.00', 'currency' => 'GBP', 'active' => true]);
        $addon = InspectionFeature::query()->create(['code' => 'edit-addon', 'name' => 'Edit add-on', 'addon_price' => '12.25', 'addon_enabled' => true, 'active' => true]);
        $package->features()->attach($addon->id, ['included' => false, 'sort_order' => 1]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-EDIT-TEST', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'public', 'status' => 'requested', 'quoted_total' => '100.00', 'currency' => 'GBP',
        ]);
        $staff = StaffUser::query()->create([
            'email' => 'booking-staff@example.test', 'display_name' => 'Booking Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);

        $this->actingAs($staff)->get(route('staff.bookings.edit', $booking))->assertOk();
        $this->put(route('staff.bookings.update-details', $booking), [
            'full_name' => 'Updated Customer', 'email' => 'updated@example.test', 'phone' => '07111111111',
            'registration' => 'CD34 EFG', 'make' => 'BMW', 'model' => '320d', 'year' => '2018',
            'package_id' => $package->id, 'addons' => [$addon->id], 'location' => 'London',
        ])->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertDatabaseHas('customers', ['id' => $booking->fresh()->customer_id, 'full_name' => 'Updated Customer', 'email' => 'updated@example.test']);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'registration' => 'CD34 EFG', 'registration_normalized' => 'CD34EFG']);
        $this->assertSame('112.25', $booking->fresh()->quoted_total);
        $this->assertDatabaseHas('booking_addons', ['booking_id' => $booking->id, 'feature_id' => $addon->id, 'price_snapshot' => '12.25']);
    }

    public function test_staff_cannot_edit_a_booking_after_inspection_has_started(): void
    {
        $customer = Customer::query()->create(['full_name' => 'Locked Customer', 'email' => 'locked@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'LK12 ABC']);
        $package = Package::query()->create(['code' => 'locked-package', 'name' => 'Locked', 'base_price' => '10.00', 'currency' => 'GBP', 'active' => true]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-LOCKED-TEST', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'staff', 'status' => 'in_progress',
            'appointment_at' => now()->addDays(2), 'quoted_total' => '10.00', 'currency' => 'GBP',
        ]);
        $staff = StaffUser::query()->create([
            'email' => 'locked-staff@example.test', 'display_name' => 'Locked Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);

        $this->actingAs($staff)->get(route('staff.bookings.edit', $booking))->assertForbidden();
        $appointmentAt = $booking->appointment_at->copy();
        $this->actingAs($staff)->put(route('staff.bookings.update', $booking), [
            'status' => 'in_progress',
            'appointment_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('appointment_at');
        $this->assertTrue($booking->fresh()->appointment_at->equalTo($appointmentAt));
        $this->actingAs($staff)->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Confirmed appointment')
            ->assertDontSee('<div class="staff-appointment-picker"', false);
    }

    public function test_staff_confirmation_requires_a_time_and_sends_confirmation_notification(): void
    {
        $customer = Customer::query()->create(['full_name' => 'Confirm Customer', 'email' => 'confirm@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'CN12 FGH']);
        $package = Package::query()->create(['code' => 'confirm-package', 'name' => 'Confirm', 'base_price' => '10.00', 'currency' => 'GBP', 'active' => true]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-CONFIRM-TEST', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'public', 'status' => 'requested', 'quoted_total' => '10.00', 'currency' => 'GBP',
        ]);
        $staff = StaffUser::query()->create([
            'email' => 'confirm-staff@example.test', 'display_name' => 'Confirm Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);
        $this->mock(NotificationDeliveryService::class)
            ->shouldReceive('booking')->once()->withArgs(fn ($sentBooking, $event) => $sentBooking->is($booking) && $event === 'confirmed')->andReturn(true);

        $this->actingAs($staff)->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Choose a new status')
            ->assertDontSee('<option value="requested">Requested</option>', false);

        $this->put(route('staff.bookings.update', $booking), ['staff_notes' => 'Contact customer later'])
            ->assertRedirect(route('staff.bookings.index'));
        $this->assertSame('requested', $booking->fresh()->status);

        $this->actingAs($staff)->put(route('staff.bookings.update', $booking), ['status' => 'confirmed'])
            ->assertSessionHasErrors('appointment_at');

        $this->put(route('staff.bookings.update', $booking), [
            'status' => 'confirmed', 'appointment_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('staff.bookings.index'));

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->confirmed_at);
    }

    public function test_confirmation_uses_first_preferred_time_when_staff_does_not_enter_one(): void
    {
        $customer = Customer::query()->create(['full_name' => 'Preferred Customer', 'email' => 'preferred@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'PR12 FGH']);
        $package = Package::query()->create(['code' => 'preferred-package', 'name' => 'Preferred', 'base_price' => '10.00', 'currency' => 'GBP', 'active' => true]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-PREFERRED-TEST', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'public', 'status' => 'requested', 'quoted_total' => '10.00', 'currency' => 'GBP',
        ]);
        $preferredAt = now()->addDays(3)->setSeconds(0);
        BookingPreference::query()->create(['booking_id' => $booking->id, 'preferred_at' => $preferredAt, 'priority' => 1]);
        $staff = StaffUser::query()->create([
            'email' => 'preferred-staff@example.test', 'display_name' => 'Preferred Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);

        $this->mock(NotificationDeliveryService::class)
            ->shouldReceive('booking')->once()->withArgs(fn ($sentBooking, $event) => $sentBooking->is($booking) && $event === 'confirmed')->andReturn(true);

        $this->actingAs($staff)->put(route('staff.bookings.update', $booking), ['status' => 'confirmed'])
            ->assertRedirect(route('staff.bookings.index'));

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertEquals($preferredAt->timestamp, $booking->fresh()->appointment_at->timestamp);
    }

    public function test_cancelling_a_booking_keeps_it_and_shows_it_on_the_dashboard(): void
    {
        $customer = Customer::query()->create(['full_name' => 'Cancelled Customer', 'email' => 'cancelled@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'CA12 XYZ']);
        $package = Package::query()->create(['code' => 'cancel-package', 'name' => 'Cancel', 'base_price' => '10.00', 'currency' => 'GBP', 'active' => true]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-CANCEL-TEST', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'public', 'status' => 'requested', 'quoted_total' => '10.00', 'currency' => 'GBP',
        ]);
        $staff = StaffUser::query()->create([
            'email' => 'cancel-staff@example.test', 'display_name' => 'Cancel Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);
        $this->mock(NotificationDeliveryService::class)
            ->shouldReceive('booking')->once()->withArgs(fn ($sentBooking, $event) => $sentBooking->is($booking) && $event === 'cancelled')->andReturn(true);

        $this->actingAs($staff)->put(route('staff.bookings.update', $booking), ['status' => 'cancelled'])
            ->assertRedirect(route('staff.bookings.index'))
            ->assertSessionHas('status', 'Booking changes saved.');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->actingAs($staff)->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee($booking->booking_reference);
        $this->actingAs($staff)->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Preferred appointment options')
            ->assertDontSee('Staff actions')
            ->assertSee('cancelled');
    }

    public function test_in_progress_booking_can_be_marked_completed(): void
    {
        $customer = Customer::query()->create(['full_name' => 'Completed Customer', 'email' => 'completed@example.test', 'phone' => '07000000000']);
        $vehicle = Vehicle::query()->create(['customer_id' => $customer->id, 'registration' => 'CO12 XYZ']);
        $package = Package::query()->create(['code' => 'completed-package', 'name' => 'Completed', 'base_price' => '10.00', 'currency' => 'GBP', 'active' => true]);
        $booking = Booking::query()->create([
            'booking_reference' => 'CER-COMPLETE-TEST', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'package_id' => $package->id, 'source' => 'public', 'status' => 'in_progress', 'appointment_at' => now()->subHour(), 'quoted_total' => '10.00', 'currency' => 'GBP',
        ]);
        $staff = StaffUser::query()->create([
            'email' => 'complete-staff@example.test', 'display_name' => 'Complete Staff',
            'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true,
        ]);

        $this->actingAs($staff)->put(route('staff.bookings.update', $booking), ['status' => 'completed'])
            ->assertRedirect(route('staff.bookings.index'));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'completed']);
        $this->actingAs($staff)->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Preferred appointment options')
            ->assertDontSee('Staff actions')
            ->assertSee('completed');
    }
}
