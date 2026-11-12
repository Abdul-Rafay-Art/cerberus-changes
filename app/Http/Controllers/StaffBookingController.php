<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Vehicle;
use App\Services\NotificationDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffBookingController
{
    public function __construct(private NotificationDeliveryService $notifications) {}

    public function create(): View
    {
        return view('staff.bookings.create', [
            'packages' => Package::query()->where('active', true)->with(['includedFeatures', 'eligibleAddons'])->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:40'],
            'registration' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]*$/'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1900,'.((int) date('Y') + 1)],
            'mileage_display' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:2000'],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('active', true)],
            'addons' => ['sometimes', 'array', 'max:20'],
            'addons.*' => ['integer', 'distinct'],
            'appointment_at' => ['nullable', 'date', 'after:now'],
            'staff_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $package = Package::query()->whereKey($validated['package_id'])->where('active', true)->with('eligibleAddons')->firstOrFail();
        $addonIds = collect($validated['addons'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($addonIds->diff($package->eligibleAddons->modelKeys())->isNotEmpty()) {
            return back()->withErrors(['addons' => 'One or more selected add-ons are not available for this package.'])->withInput();
        }

        $totalCents = (int) round((float) $package->base_price * 100);
        $selectedAddons = $package->eligibleAddons->whereIn('id', $addonIds);
        foreach ($selectedAddons as $addon) {
            $totalCents += (int) round((float) $addon->addon_price * 100);
        }

        $booking = DB::transaction(function () use ($validated, $package, $selectedAddons, $totalCents, $request): Booking {
            $customer = Customer::query()->updateOrCreate(
                ['email' => mb_strtolower(trim($validated['email']))],
                ['full_name' => trim($validated['full_name']), 'phone' => trim($validated['phone'])]
            );
            $registration = strtoupper(trim($validated['registration']));
            $vehicle = Vehicle::query()->create([
                'customer_id' => $customer->id,
                'registration' => $registration,
                'make' => $validated['make'] ?? null,
                'model' => $validated['model'] ?? null,
                'year' => $validated['year'] ?? null,
                'mileage_display' => $validated['mileage_display'] ?? null,
            ]);
            $booking = Booking::query()->create([
                'booking_reference' => 'CER-'.Str::upper(Str::random(16)),
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'package_id' => $package->id,
                'source' => 'staff',
                'status' => empty($validated['appointment_at']) ? 'requested' : 'confirmed',
                'confirmed_at' => empty($validated['appointment_at']) ? null : now(),
                'appointment_at' => $validated['appointment_at'] ?? null,
                'location' => $validated['location'] ?? null,
                'staff_notes' => $validated['staff_notes'] ?? null,
                'quoted_total' => number_format($totalCents / 100, 2, '.', ''),
                'currency' => $package->currency,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            foreach ($selectedAddons as $addon) {
                DB::table('booking_addons')->insert([
                    'booking_id' => $booking->id, 'feature_id' => $addon->id,
                    'price_snapshot' => $addon->addon_price, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $booking;
        });

        $emailSent = $this->notifications->booking($booking, $booking->status === 'confirmed' ? 'confirmed' : 'requested');

        return redirect()->route('staff.bookings.show', $booking)
            ->with('status', 'Staff booking created.')
            ->with('email_delivery_failed', ! $emailSent);
    }

    public function index(Request $request): View
    {
        $validated = $request->validate(['appointment_from' => ['nullable', 'date'], 'appointment_to' => ['nullable', 'date', 'after_or_equal:appointment_from']]);
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $bookings = Booking::query()
            ->with(['customer:id,full_name,email,phone', 'vehicle:id,registration,make,model', 'package:id,name', 'inspection:id,booking_id'])
            ->when(in_array($status, ['requested', 'confirmed', 'in_progress', 'completed', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('booking_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('full_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('vehicle', fn ($vehicle) => $vehicle->where('registration', 'like', '%'.$search.'%')->orWhere('registration_normalized', 'like', '%'.preg_replace('/[^A-Z0-9]/', '', strtoupper($search)).'%'));
                });
            })
            ->when($validated['appointment_from'] ?? null, fn ($query, string $date) => $query->whereDate('appointment_at', '>=', $date))
            ->when($validated['appointment_to'] ?? null, fn ($query, string $date) => $query->whereDate('appointment_at', '<=', $date))
            ->latest()
            ->paginate(7)
            ->withQueryString();

        $counts = Booking::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $todayAppointments = Booking::query()->whereIn('status', ['confirmed', 'in_progress'])->whereBetween('appointment_at', [now()->startOfDay(), now()->endOfDay()])->count();
        $upcomingAppointments = Booking::query()->whereIn('status', ['confirmed', 'in_progress'])->whereBetween('appointment_at', [now(), now()->addDays(7)->endOfDay()])->count();
        $nextAppointments = Booking::query()->with(['customer:id,full_name', 'vehicle:id,registration'])->whereIn('status', ['confirmed', 'in_progress'])->whereNotNull('appointment_at')->where('appointment_at', '>=', now())->orderBy('appointment_at')->limit(5)->get();

        $appointmentFrom = $validated['appointment_from'] ?? '';
        $appointmentTo = $validated['appointment_to'] ?? '';

        return view('staff.bookings.index', compact('bookings', 'counts', 'status', 'search', 'appointmentFrom', 'appointmentTo', 'todayAppointments', 'upcomingAppointments', 'nextAppointments'));
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate(['appointment_from' => ['nullable', 'date'], 'appointment_to' => ['nullable', 'date', 'after_or_equal:appointment_from']]);
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());
        $bookings = Booking::query()->with(['customer:id,full_name,email,phone', 'vehicle:id,registration,make,model', 'package:id,name'])
            ->when(in_array($status, ['requested', 'confirmed', 'in_progress', 'completed', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('booking_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('full_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('vehicle', fn ($vehicle) => $vehicle->where('registration', 'like', '%'.$search.'%')->orWhere('registration_normalized', 'like', '%'.preg_replace('/[^A-Z0-9]/', '', strtoupper($search)).'%'));
                });
            })
            ->when($validated['appointment_from'] ?? null, fn ($query, string $date) => $query->whereDate('appointment_at', '>=', $date))
            ->when($validated['appointment_to'] ?? null, fn ($query, string $date) => $query->whereDate('appointment_at', '<=', $date))
            ->latest()->cursor();
        $safe = static function (?string $value): string {
            $value = $value ?? '';

            return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
        };

        return response()->streamDownload(function () use ($bookings, $safe): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Reference', 'Customer', 'Email', 'Phone', 'Registration', 'Vehicle', 'Package', 'Status', 'Appointment', 'Quoted total', 'Created']);
            foreach ($bookings as $booking) {
                fputcsv($output, [
                    $safe($booking->booking_reference), $safe($booking->customer->full_name), $safe($booking->customer->email), $safe($booking->customer->phone),
                    $safe($booking->vehicle->registration), $safe(trim(($booking->vehicle->make ?? '').' '.($booking->vehicle->model ?? ''))), $safe($booking->package->name),
                    $safe($booking->status), $booking->appointment_at?->toIso8601String(), $booking->quoted_total.' '.$booking->currency, $booking->created_at?->toIso8601String(),
                ]);
            }
            fclose($output);
        }, 'cerberus-bookings-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['customer', 'vehicle', 'package', 'preferences', 'inspection', 'addons']);

        return view('staff.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking): View
    {
        abort_unless(in_array($booking->status, ['requested', 'confirmed'], true), 403, 'Booking details cannot be changed after an inspection has started.');
        $booking->load(['customer', 'vehicle', 'package', 'addons']);
        $packages = Package::query()->where('active', true)->with(['includedFeatures', 'eligibleAddons'])->orderBy('sort_order')->get();

        return view('staff.bookings.edit', compact('booking', 'packages'));
    }

    public function updateDetails(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless(in_array($booking->status, ['requested', 'confirmed'], true), 403, 'Booking details cannot be changed after an inspection has started.');

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'registration' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]*$/'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1900,'.((int) date('Y') + 1)],
            'mileage_display' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:2000'],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('active', true)],
            'addons' => ['sometimes', 'array', 'max:20'],
            'addons.*' => ['required', 'integer', 'distinct'],
        ]);

        $package = Package::query()->whereKey($validated['package_id'])->where('active', true)->with('eligibleAddons')->firstOrFail();
        $addonIds = collect($validated['addons'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($addonIds->diff($package->eligibleAddons->modelKeys())->isNotEmpty()) {
            return back()->withErrors(['addons' => 'One or more selected add-ons are not available for this package.'])->withInput();
        }

        $selectedAddons = $package->eligibleAddons->whereIn('id', $addonIds);
        $totalCents = (int) round((float) $package->base_price * 100);
        foreach ($selectedAddons as $addon) {
            $totalCents += (int) round((float) $addon->addon_price * 100);
        }

        DB::transaction(function () use ($booking, $request, $validated, $package, $selectedAddons, $totalCents): void {
            $customer = Customer::query()->updateOrCreate(
                ['email' => mb_strtolower(trim($validated['email']))],
                ['full_name' => trim($validated['full_name']), 'phone' => trim($validated['phone']), 'address' => $validated['address'] ?? null]
            );
            $booking->vehicle->fill([
                'customer_id' => $customer->id,
                'registration' => strtoupper(trim($validated['registration'])),
                'make' => $validated['make'] ?? null,
                'model' => $validated['model'] ?? null,
                'year' => $validated['year'] ?? null,
                'mileage_display' => $validated['mileage_display'] ?? null,
            ])->save();

            $booking->fill([
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'location' => $validated['location'] ?? null,
                'quoted_total' => number_format($totalCents / 100, 2, '.', ''),
                'currency' => $package->currency,
                'updated_by' => $request->user()->id,
            ])->save();

            DB::table('booking_addons')->where('booking_id', $booking->id)->delete();
            foreach ($selectedAddons as $addon) {
                DB::table('booking_addons')->insert([
                    'booking_id' => $booking->id, 'feature_id' => $addon->id,
                    'price_snapshot' => $addon->addon_price, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('staff.bookings.show', $booking)->with('status', 'Booking details and quote updated.');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['requested', 'confirmed', 'in_progress', 'completed', 'cancelled'])],
            'appointment_at' => ['nullable', Rule::prohibitedIf($booking->status === 'in_progress'), 'date', Rule::when($request->input('status') === 'confirmed', ['after:now'])],
            'staff_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $newStatus = $validated['status'] ?? $booking->status;
        $allowedTransitions = [
            'requested' => ['requested', 'confirmed', 'cancelled'],
            'confirmed' => ['confirmed', 'cancelled'],
            'in_progress' => ['in_progress', 'completed', 'cancelled'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];

        if (! in_array($newStatus, $allowedTransitions[$booking->status] ?? [], true)) {
            return back()->withErrors(['status' => 'This booking status cannot be changed using this action.']);
        }
        $appointmentAt = $validated['appointment_at'] ?? null;
        if ($newStatus === 'confirmed' && empty($appointmentAt)) {
            $appointmentAt = $booking->preferences()->orderBy('priority')->value('preferred_at');
        }
        if ($newStatus === 'confirmed' && empty($appointmentAt)) {
            return back()->withErrors(['appointment_at' => 'Choose an appointment time before confirming the booking.'])->withInput();
        }

        $originalStatus = $booking->status;
        $originalAppointment = $booking->appointment_at?->timestamp;
        $booking->status = $newStatus;
        $booking->appointment_at = $appointmentAt ?? $booking->appointment_at;
        $booking->staff_notes = $validated['staff_notes'] ?? null;
        $booking->updated_by = $request->user()->id;
        if ($newStatus === 'confirmed' && ! $booking->confirmed_at) {
            $booking->confirmed_at = now();
        }
        $booking->save();

        $mailEvent = null;
        if ($originalStatus !== $booking->status) {
            $mailEvent = $booking->status === 'confirmed' ? 'confirmed' : ($booking->status === 'cancelled' ? 'cancelled' : null);
        } elseif ($booking->status === 'confirmed' && $originalAppointment !== $booking->appointment_at?->timestamp) {
            $mailEvent = 'rescheduled';
        }
        $emailSent = $mailEvent ? $this->notifications->booking($booking, $mailEvent) : true;

        return redirect()->route('staff.bookings.index')->with('status', 'Booking changes saved.')
            ->with('email_delivery_failed', ! $emailSent);
    }
}
