<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalDemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $adminId = DB::table('staff_users')->where('email', env('CERBERUS_DEMO_ADMIN_EMAIL'))->value('id');
        $packages = DB::table('packages')->where('is_demo', true)->where('active', true)->pluck('id', 'code');
        if (! $adminId || $packages->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($adminId, $packages): void {
            $createBooking = function (array $data) use ($adminId, $packages): int {
                $customerId = DB::table('customers')->where('email', $data['email'])->value('id');
                if (! $customerId) {
                    $customerId = (string) Str::uuid();
                    DB::table('customers')->insert([
                        'id' => $customerId, 'full_name' => $data['name'], 'email' => $data['email'],
                        'phone' => '07000000000', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $vehicleId = DB::table('vehicles')->where('registration_normalized', $data['registration'])->value('id');
                if (! $vehicleId) {
                    $vehicleId = (string) Str::uuid();
                    DB::table('vehicles')->insert([
                        'id' => $vehicleId, 'customer_id' => $customerId, 'registration' => $data['registration'],
                        'registration_normalized' => $data['registration'], 'make' => $data['make'], 'model' => $data['model'],
                        'year' => $data['year'], 'mileage_display' => $data['mileage'], 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $packageId = $packages[$data['package']] ?? null;
                if (! $packageId) {
                    return 0;
                }
                DB::table('bookings')->updateOrInsert(
                    ['booking_reference' => $data['reference']],
                    [
                        'customer_id' => $customerId, 'vehicle_id' => $vehicleId, 'package_id' => $packageId,
                        'source' => 'public', 'status' => $data['status'], 'confirmed_at' => $data['confirmed_at'],
                        'appointment_at' => $data['appointment_at'], 'location' => 'DEMO LOCATION — local testing only',
                        'staff_notes' => 'DEMO DATA — local workflow testing only.', 'quoted_total' => $data['total'],
                        'currency' => 'GBP', 'created_at' => now(), 'updated_at' => now(), 'created_by' => null, 'updated_by' => $adminId,
                    ]
                );
                $bookingId = (int) DB::table('bookings')->where('booking_reference', $data['reference'])->value('id');
                DB::table('booking_preferences')->updateOrInsert(
                    ['booking_id' => $bookingId, 'priority' => 1],
                    ['preferred_at' => $data['appointment_at'] ?? now()->addDays(2), 'created_at' => now(), 'updated_at' => now()]
                );

                return $bookingId;
            };

            $now = now();
            $createBooking(['reference' => 'DEMO-REQUESTED-001', 'name' => 'Demo Requested Customer', 'email' => 'demo.requested@example.test', 'registration' => 'DEMO 01', 'make' => 'Demo Motors', 'model' => 'Request', 'year' => 2021, 'mileage' => '42000 miles', 'package' => 'gold', 'status' => 'requested', 'confirmed_at' => null, 'appointment_at' => null, 'total' => '249.00']);
            $createBooking(['reference' => 'DEMO-CONFIRMED-001', 'name' => 'Demo Confirmed Customer', 'email' => 'demo.confirmed@example.test', 'registration' => 'DEMO 02', 'make' => 'Demo Motors', 'model' => 'Confirmed', 'year' => 2020, 'mileage' => '51000 miles', 'package' => 'gold', 'status' => 'confirmed', 'confirmed_at' => $now, 'appointment_at' => $now->copy()->addDays(2), 'total' => '249.00']);
            $inProgressId = $createBooking(['reference' => 'DEMO-INPROGRESS-001', 'name' => 'Demo Inspector Customer', 'email' => 'demo.inspection@example.test', 'registration' => 'DEMO 03', 'make' => 'Demo Motors', 'model' => 'Inspection', 'year' => 2019, 'mileage' => '63000 miles', 'package' => 'gold', 'status' => 'in_progress', 'confirmed_at' => $now, 'appointment_at' => $now->copy()->subHour(), 'total' => '249.00']);
            $completedId = $createBooking(['reference' => 'DEMO-COMPLETED-001', 'name' => 'Demo Completed Customer', 'email' => 'demo.completed@example.test', 'registration' => 'DEMO 04', 'make' => 'Demo Motors', 'model' => 'Completed', 'year' => 2018, 'mileage' => '72000 miles', 'package' => 'gold', 'status' => 'completed', 'confirmed_at' => $now->copy()->subDays(2), 'appointment_at' => $now->copy()->subDays(1), 'total' => '249.00']);
            $createBooking(['reference' => 'DEMO-REQUESTED-002', 'name' => 'Demo New Customer', 'email' => 'demo.new@example.test', 'registration' => 'DEMO 05', 'make' => 'Demo Motors', 'model' => 'New Request', 'year' => 2022, 'mileage' => '18000 miles', 'package' => 'gold', 'status' => 'requested', 'confirmed_at' => null, 'appointment_at' => null, 'total' => '249.00']);
            $createBooking(['reference' => 'DEMO-CONFIRMED-002', 'name' => 'Demo Fleet Customer', 'email' => 'demo.fleet@example.test', 'registration' => 'DEMO 06', 'make' => 'Demo Motors', 'model' => 'Fleet', 'year' => 2021, 'mileage' => '36000 miles', 'package' => 'gold', 'status' => 'confirmed', 'confirmed_at' => $now, 'appointment_at' => $now->copy()->addDays(4), 'total' => '249.00']);
            $createBooking(['reference' => 'DEMO-INPROGRESS-002', 'name' => 'Demo Workshop Customer', 'email' => 'demo.workshop@example.test', 'registration' => 'DEMO 07', 'make' => 'Demo Motors', 'model' => 'Workshop', 'year' => 2020, 'mileage' => '58000 miles', 'package' => 'gold', 'status' => 'in_progress', 'confirmed_at' => $now, 'appointment_at' => $now->copy()->subHours(2), 'total' => '249.00']);
            $createBooking(['reference' => 'DEMO-COMPLETED-002', 'name' => 'Demo Report Customer', 'email' => 'demo.report@example.test', 'registration' => 'DEMO 08', 'make' => 'Demo Motors', 'model' => 'Report', 'year' => 2017, 'mileage' => '81000 miles', 'package' => 'gold', 'status' => 'completed', 'confirmed_at' => $now->copy()->subDays(4), 'appointment_at' => $now->copy()->subDays(3), 'total' => '249.00']);
            $createBooking(['reference' => 'DEMO-CANCELLED-001', 'name' => 'Demo Cancelled Customer', 'email' => 'demo.cancelled@example.test', 'registration' => 'DEMO 09', 'make' => 'Demo Motors', 'model' => 'Cancelled', 'year' => 2016, 'mileage' => '90000 miles', 'package' => 'gold', 'status' => 'cancelled', 'confirmed_at' => null, 'appointment_at' => $now->copy()->subDays(5), 'total' => '249.00']);

            $feature = DB::table('inspection_features')->where('code', 'local-demo-basic-checks')->first();
            $questions = DB::table('feature_questions')->join('questions', 'questions.id', '=', 'feature_questions.question_id')->where('feature_questions.feature_id', $feature?->id)->orderBy('feature_questions.sort_order')->select('questions.*')->get();
            foreach ([[$inProgressId, 'in_progress'], [$completedId, 'completed']] as [$bookingId, $status]) {
                if (! $bookingId || ! $feature) {
                    continue;
                }
                DB::table('inspections')->updateOrInsert(
                    ['booking_id' => $bookingId],
                    ['status' => $status, 'started_at' => $now->subHour(), 'completed_at' => $status === 'completed' ? $now->subMinutes(30) : null, 'package_snapshot' => json_encode(['name' => 'DEMO package', 'is_demo' => true, 'video_included' => false]), 'feature_snapshot' => json_encode([['name' => $feature->name, 'selection_type' => 'included']]), 'rating_scheme_snapshot' => json_encode(config('cerberus.rating_scheme')), 'created_by' => $adminId, 'created_at' => now(), 'updated_at' => now()]
                );
                $inspectionId = (int) DB::table('inspections')->where('booking_id', $bookingId)->value('id');
                foreach ($questions as $index => $question) {
                    DB::table('inspection_checks')->updateOrInsert(
                        ['inspection_id' => $inspectionId, 'sort_order' => $index + 1],
                        ['question_id' => $question->id, 'section_title_snapshot' => 'DEMO ONLY — Sample checks', 'question_text_snapshot' => $question->prompt, 'rating_code' => $status === 'completed' ? 'good' : null, 'notes' => $status === 'completed' ? 'DEMO observation.' : null, 'measurements' => null, 'last_saved_at' => $status === 'completed' ? now() : null, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        });
    }
}
