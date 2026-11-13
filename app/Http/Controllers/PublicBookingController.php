<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPreference;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Vehicle;
use App\Services\NotificationDeliveryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicBookingController
{
    public function __construct(private NotificationDeliveryService $notifications) {}

    public function create(): View
    {
        $packages = Package::query()
            ->where('active', true)
            ->with(['includedFeatures', 'eligibleAddons'])
            ->orderBy('sort_order')
            ->get();

        return view('bookings.create', [
            'packages' => $packages,
            'submissionKey' => (string) Str::uuid(),
            'demoMode' => $packages->contains(fn (Package $package) => $package->is_demo),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'preferences' => collect($request->input('preferences', []))->filter(fn ($preference) => filled($preference))->values()->all(),
        ]);
        $validated = $request->validate([
            'submission_key' => ['required', 'uuid'],
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'registration' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]*$/'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1900,'.((int) date('Y') + 1)],
            'mileage_display' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:2000'],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('active', true)],
            'addons' => ['sometimes', 'array', 'max:20'],
            'addons.*' => ['integer', 'distinct'],
            'preferences' => ['required', 'array', 'min:1', 'max:3'],
            'preferences.*' => ['required', 'date', 'after:now', 'distinct'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ]);

        if ($existing = Booking::query()->where('submission_key', $validated['submission_key'])->first()) {
            return redirect()->route('bookings.received', $existing->booking_reference);
        }

        $package = Package::query()->whereKey($validated['package_id'])->where('active', true)
            ->with(['includedFeatures', 'eligibleAddons'])->firstOrFail();
        $eligibleIds = $package->eligibleAddons->modelKeys();
        $addonIds = collect($validated['addons'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($addonIds->diff($eligibleIds)->isNotEmpty()) {
            return back()->withErrors(['addons' => 'One or more selected add-ons are not available for this package.'])->withInput();
        }

        $totalCents = (int) round(((float) $package->base_price) * 100);
        $selectedAddons = $package->eligibleAddons->whereIn('id', $addonIds);
        foreach ($selectedAddons as $addon) {
            $totalCents += (int) round(((float) $addon->addon_price) * 100);
        }

        try {
            $booking = DB::transaction(function () use ($validated, $package, $selectedAddons, $totalCents): Booking {
                $customer = Customer::query()->updateOrCreate(
                    ['email' => mb_strtolower(trim($validated['email']))],
                    [
                        'full_name' => trim($validated['full_name']),
                        'phone' => trim($validated['phone']),
                        'address' => $validated['address'] ?? null,
                    ]
                );

                $registration = strtoupper(trim($validated['registration']));
                $normalizedRegistration = preg_replace('/[^A-Z0-9]/', '', $registration);
                $vehicle = Vehicle::query()->create([
                    'customer_id' => $customer->id,
                    'registration' => $registration,
                    'make' => $validated['make'] ?? null,
                    'model' => $validated['model'] ?? null,
                    'year' => $validated['year'] ?? null,
                    'mileage_display' => $validated['mileage_display'] ?? null,
                ]);
                $vehicle->registration_normalized = $normalizedRegistration;
                $vehicle->save();

                $booking = Booking::query()->create([
                    'booking_reference' => 'CER-'.Str::upper(Str::random(16)),
                    'submission_key' => $validated['submission_key'],
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'package_id' => $package->id,
                    'source' => 'public',
                    'status' => 'requested',
                    'location' => $validated['location'] ?? null,
                    'quoted_total' => number_format($totalCents / 100, 2, '.', ''),
                    'currency' => $package->currency,
                ]);

                foreach (array_values($validated['preferences']) as $index => $preferredAt) {
                    BookingPreference::query()->create([
                        'booking_id' => $booking->id,
                        'preferred_at' => $preferredAt,
                        'priority' => $index + 1,
                    ]);
                }

                foreach ($selectedAddons as $addon) {
                    DB::table('booking_addons')->insert([
                        'booking_id' => $booking->id,
                        'feature_id' => $addon->id,
                        'price_snapshot' => $addon->addon_price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $booking;
            });
        } catch (QueryException $exception) {
            $booking = Booking::query()->where('submission_key', $validated['submission_key'])->first();
            if (! $booking) {
                throw $exception;
            }
        }

        $emailSent = $this->notifications->booking($booking, 'requested');

        return redirect()->route('bookings.received', $booking->booking_reference)->with('email_delivery_failed', ! $emailSent);
    }

    public function received(string $bookingReference): View
    {
        $booking = Booking::query()
            ->where('booking_reference', $bookingReference)
            ->with(['customer:id,full_name', 'vehicle:id,registration', 'package:id,name,is_demo', 'preferences'])
            ->firstOrFail();

        return view('bookings.received', compact('booking'));
    }
}
