<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoldChecklistSeeder extends Seeder
{
    /**
     * Initial transcription from CERBERUS GOLD PPVI LIST-1.pdf.
     * Wording remains subject to client confirmation before production use.
     */
    public function run(): void
    {
        $catalogue = [
            'interior-comfort' => ['Interior and comfort', [
                'Seat upholstery and condition', 'Seat adjustment and locking', 'Rear-seat folding and locking', 'Head restraints',
                'Seat belts, buckles and retraction', 'Visible ISOFIX mountings', 'Steering wheel condition and adjustment',
                'Dashboard and centre console condition', 'Door trims and interior fittings', 'Interior handles and door release',
                'Door seals, hinges and straps', 'Interior sill trims', 'Carpets and floor mats', 'Headlining', 'Sun visors and vanity mirrors',
                'Interior rear-view mirror', 'Glovebox and storage compartments', 'Parcel shelf / luggage cover', 'Boot trim and load floor',
                'Dampness and water-ingress evidence', 'Interior odours', 'Pedal rubber condition',
            ]],
            'electrical-systems-controls' => ['Electrical systems and controls', [
                'Central locking and remote keys', 'Child locks', 'Boot / tailgate locking and powered operation', 'Window operation',
                'Exterior mirror adjustment, folding and heating', 'Sunroof and roof-blind operation', 'Convertible roof operation',
                'Seat heating, ventilation and memory', 'Instrument display and gauges', 'Dashboard illumination and dimming',
                'Interior and luggage-area lights', 'Headlights — dipped and main beam', 'Sidelights and daytime running lights',
                'Rear lights and number-plate lights', 'Brake lights', 'Indicators and hazard lights', 'Reverse lights',
                'Front and rear fog lights', 'Additional fitted lights', 'Horn', 'Front and rear wipers / washers',
                'Headlamp washers / wipers', 'Heater blower and airflow controls', 'Heating and demisting performance',
                'Air-conditioning operation and vent temperature', 'Heated screens', 'Radio, CD, media and speakers',
                'Infotainment screen and navigation', 'Bluetooth and phone connectivity', 'USB, 12V sockets and wireless charging',
                'Parking sensors', 'Parking / surround-view cameras', 'Other fitted electrical controls',
                'Switches/controls (condition & operation)', 'Window locks',
            ]],
            'bodywork-exterior' => ['Bodywork and exterior', [
                'Body panel damage', 'Panel alignment and gaps', 'Paintwork condition', 'Paint-depth readings',
                'Previous repair evidence and quality', 'Exterior corrosion', 'Windscreen condition', 'Side and rear glass condition',
                'Exterior mirror glass and housings', 'Bumper condition and security', 'Exterior trims, grilles and mouldings',
                'Exterior door handles', 'Bonnet hinges, latch and safety catch', 'Boot / tailgate hinges, supports and latching',
                'Fuel-filler flap condition and operation', 'Number plate condition and security',
                'Sunroof / panoramic-roof physical condition', 'Convertible roof and seals',
                'Convertible roof storage cover - condition and storage', 'Mud flaps — where fitted', 'Door locks operation - manual blade',
            ]],
            'wheels-tyres' => ['Wheel and tyres', [
                'Front passenger side tyre', 'Front driver side tyre', 'Rear passenger side tyre', 'Rear driver side tyre',
                'Tyre compatibility and fitment', 'Wheel trims and centre caps', 'Spare wheel and tyre', 'Spare wheel carrier and security',
                'Locking wheel nut key', 'Jack and wheel changing tools', 'Tyre repair kit', 'Alloy wheel condition (if applicable)',
                'Steel wheel condition',
            ]],
            'engine-compartment' => ['Engine compartment', [
                'Engine covers and fittings', 'Engine mountings — visible condition and security', 'Engine oil level', 'Engine oil condition',
                'Oil filler cap and dipstick condition', 'Engine oil leaks', 'Auxiliary drive belt, pulleys and tensioner',
                'Timing belt / cover — condition where visible without dismantling', 'Air-filter element — where accessible',
                'Air intake and breather pipes', 'Turbo / supercharger and intercooler pipework', 'Vacuum hoses and connections',
                'Wiring, connectors and engine earth connections', 'Visible ignition components', 'Evidence of modifications or makeshift repairs',
                'Borescope assessment — where justified and accessible without dismantling', 'Engine bay cleanliness',
                'Fuel Injection Leak Off Pipe Condition (if visable)', 'Other fluid leaks',
                'Screen wash reservoir, cap, accessible pipes and leaks',
            ]],
            'cooling-system' => ['Cooling system', [
                'Coolant level', 'Coolant condition', 'Expansion tank condition and security', 'Radiator / expansion - tank cap and seal',
                'Radiator condition and mountings', 'Coolant hoses, pipes and connections', 'Thermostat housing condition',
                'Water-pump condition — visable leaks / damage / noise', 'Cooling system leak evidence', 'Cooling system pressure test',
                'Combustion - gas block test', 'Cooling fan, shroud and wiring condition', 'Previous overheating evidence',
            ]],
            'fuel-system' => ['Fuel system', [
                'Fuel tank condition', 'Fuel tank mountings, straps and protective shields', 'Filler neck and connecting hoses',
                'Fuel cap / capless filler seal', 'Fuel supply and return pipes', 'Fuel filter housing and connections',
                'Accessible fuel pumps and connections', 'Fuel rail and high pressure pipes', 'Injectors and seals', 'Breather pipes',
                'Fuel leak evidence',
            ]],
            'battery-starting-charging' => ['Battery starting and charging', [
                'Battery casing condition', 'Battery mounting and security', 'Battery terminals and connections', 'Battery cables and earth straps',
                'Battery vent pipe — where fitted', 'Battery specification suitability', 'Battery voltage before starting',
                'Battery test and report', 'Starter motor operation and noise', 'Starter motor wiring and mountings',
                'Alternator condition, connections and mountings', 'Charging performance — electrical loads off/on', 'Warm charging performance',
            ]],
            'cold-start-checks' => ['Cold start checks', [
                'Cold starting performance', 'Cold engine noise', 'Cold running and idle stability', 'Exhaust smoke on cold start',
            ]],
            'front-suspension-steering' => ['Front suspension and steering', [
                'Front springs and seating', 'Front shock absorbers / struts', 'Strut top mounts and bearings',
                'Bump stops and suspension dust covers', 'Suspension arms, links, bushes and fixings', 'Suspension ball joints',
                'Anti-roll bar, bushes and drop links', 'Front hubs and wheel bearings', 'Steering rack / box and mountings',
                'Track rods and track-rod ends', 'Steering rack gaiters', 'Steering column, joints and couplings',
                'Power steering components and leaks', 'Power steering fluid level and condition', 'Front subframe and mountings',
                'Front air-suspension components', 'Suspension height adjustment and self-levelling',
            ]],
            'rear-suspension' => ['Rear suspension', [
                'Rear springs and seating', 'Rear shock absorbers / struts', 'Shock absorber / strut mountings',
                'Bump stops and suspension dust covers', 'Suspension arms, links, bushes and fixings', 'Suspension ball joints',
                'Anti-roll bar, bushes and drop links', 'Rear hubs and wheel bearings', 'Rear axle / beam and mountings',
                'Rear subframe and mountings', 'Rear air suspension components',
            ]],
            'braking-system' => ['Braking system', [
                'Front pads and discs', 'Rear pads and discs', 'Brake drums — external condition', 'Brake calipers, carriers and fixings',
                'Brake backplates and dust shields', 'Rigid brake pipes and connections', 'Flexible brake hoses',
                'Brake fluid level and reservoir condition', 'Brake fluid test', 'Brake master cylinder — condition, security and leaks',
                'Brake servo, vacuum supply and assistance', 'Brake pedal and linkage — condition, travel and pressure retention',
                'Parking-brake cables, linkages and actuators', 'ABS hydraulic unit, visible wiring and sensor connections',
                'Brake binding / wheel rotation', 'Post road test brake temperatures — thermal imaging', 'Uneven brake wear',
                'Visible brake fluid leaks',
            ]],
            'clutch-transmission' => ['Clutch and transmission', [
                'Clutch pedal and linkage', 'Clutch cable and adjuster',
                'Clutch master cylinder, pipes, hoses and separate reservoir', 'Clutch slave cylinder — where accessible',
                'Gear selector, cables and linkages', 'Gearbox casing, seals and leaks', 'Gearbox mountings',
                'Transmission fluid level and condition — where accessible', 'Transmission cooler and pipework — where fitted',
                'Front driveshafts, joints, gaiters and support bearings', 'Rear driveshafts, joints and gaiters',
                'Propshafts, universal/sliding joints and support bearings', 'Differential casings, mountings and leaks',
                'Transfer box / power transfer unit — condition, mountings and leaks',
                'Four wheel drive couplings and connections — where accessible',
            ]],
            'underside-condition' => ['Underside condition', [
                'Chassis rails and structural members', 'Floorpan condition', 'Structural sills and jacking points',
                'Wheel arches and inner wings — underside areas', 'Underbody corrosion protection', 'Previous structural repairs and welding',
                'Undertrays, protective covers and fixings', 'Underbody heat shields and fixings',
                'Underbody wiring, connectors and retaining clips', 'Towbar, mounting points and fixings — where fitted',
                'Corrosion - floor/chassis', 'Engine underside leakage',
            ]],
            'exhaust-emissions' => ['Exhaust and emissions system', [
                'Exhaust manifold and accessible connections', 'Exhaust pipes and silencers', 'Exhaust joints, clamps and seals',
                'Exhaust flexible sections', 'Exhaust mountings and rubber hangers', 'Exhaust clearance from surrounding components',
                'Exhaust gas leaks', 'Catalytic converter / particulate filter — external condition',
                'Exhaust sensors, wiring and pressure pipes', 'Visible exhaust modifications or tampering',
                'Adblue level / remaining level', 'Adblue filler, cap, tank and accessible pipes',
            ]],
            'road-test' => ['Road Test', [
                'Starting system/ignition lock', 'Exhaust smoke under load', 'Engine noise under load', 'Engine response',
                'Turbo/super charger operation & noise', 'Parking brake application - release and holding performance',
                'Foot brake operation', 'Manual gearbox operation - gear selection and noise',
                'Automatic shift quality - upshifts and downshifts', 'Clutch operation', 'Accelerator Pedal Operation',
                'Road holding/stability', 'Cruise control / Apaptive cruise control / Speed limiter operation',
                'Lane departure warning / Lane keeping / Steering assistance operation', 'Steering wheel alignment',
                'Steering response and feel', 'Four wheel drive operation', 'Final drive operation/noise level', 'Abnormal noises and rattles',
            ]],
            'warning-lights-diagnostics' => ['Warning Lights and Diagnostics', [
                'Dashboard warning lights - ignition on', 'Dashboard warning lights and messages - engine running',
                'Road test warnings and messages', 'Pre road test diagnostic scan', 'Post road test diagnostic scan',
            ]],
            'post-road-test-checks' => ['Post Road Test Checks', [
                'Hot restart', 'Hot idle stability', 'Engine noise when warm', 'Operating temperature', 'Cooling fan operation',
                'Post road test leak check', 'Exhaust smoke at operating temperature', 'Abnormal smells',
                'Post road test warning lights/messages', 'Brake temperatures checked',
            ]],
        ];

        DB::transaction(function () use ($catalogue): void {
            DB::table('packages')->whereIn('code', [
                'pending-package-1', 'pending-package-2', 'pending-package-3', 'pending-package-4',
            ])->update(['active' => false, 'updated_at' => now()]);

            $featureId = DB::table('inspection_features')->where('code', 'gold-full-inspection')->value('id');
            if (! $featureId) {
                $featureId = DB::table('inspection_features')->insertGetId([
                    'code' => 'gold-full-inspection', 'name' => 'Gold full inspection',
                    'description' => 'Provisional checklist transcribed from the client-provided Gold PPVI list.',
                    'addon_price' => null, 'addon_enabled' => false, 'active' => true, 'sort_order' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $goldPackageId = DB::table('packages')->where('code', 'gold')->value('id');
            if (! $goldPackageId) {
                $goldPackageId = DB::table('packages')->insertGetId([
                    'code' => 'gold', 'name' => 'Gold',
                    'description' => 'Provisional Gold checklist. Package price and final inclusions pending client approval.',
                    'base_price' => 0, 'currency' => 'GBP', 'active' => false, 'sort_order' => 5,
                    'video_included' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('packages')->where('id', $goldPackageId)->update(['video_included' => true, 'updated_at' => now()]);
            DB::table('package_features')->updateOrInsert(
                ['package_id' => $goldPackageId, 'feature_id' => $featureId],
                ['included' => true, 'sort_order' => 1]
            );

            $sectionOrder = 0;
            foreach ($catalogue as $code => [$title, $prompts]) {
                $sectionOrder++;
                $sectionId = DB::table('inspection_sections')->where('code', $code)->value('id');
                if (! $sectionId) {
                    $sectionId = DB::table('inspection_sections')->insertGetId([
                        'code' => $code, 'title' => $title, 'sort_order' => $sectionOrder, 'active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                foreach ($prompts as $index => $prompt) {
                    $questionCode = 'gold-'.str_replace('-', '', $code).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                    $questionId = DB::table('questions')->where('code', $questionCode)->value('id');
                    if (! $questionId) {
                        $questionId = DB::table('questions')->insertGetId([
                            'section_id' => $sectionId, 'code' => $questionCode, 'prompt' => $prompt,
                            'response_type' => 'rating', 'required' => false, 'sort_order' => $index + 1,
                            'active' => true, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    DB::table('feature_questions')->updateOrInsert(
                        ['feature_id' => $featureId, 'question_id' => $questionId],
                        ['sort_order' => ($sectionOrder * 1000) + $index + 1]
                    );
                }
            }
        });
    }
}
