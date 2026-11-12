<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SilverBronzeChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $catalogues = [
            'silver' => [
                'name' => 'Silver',
                'description' => 'Silver pre-purchase vehicle inspection checklist.',
                'base_price' => '170.00',
                'sort_order' => 2,
                'sections' => [
                    'interior-comfort' => ['Interior and Comfort', [
                        'Seat upholstery and condition', 'Seat adjustment and locking', 'Rear-seat folding and locking', 'Head restraints',
                        'Seat belts, buckles and retraction', 'Visible ISOFIX mountings', 'Steering wheel condition and adjustment',
                        'Dashboard and centre console condition', 'Door trims and interior fittings', 'Interior handles and door release',
                        'Door seals, hinges and straps', 'Interior sill trims', 'Carpets and floor mats', 'Headlining',
                        'Sun visors and vanity mirrors', 'Interior rear-view mirror', 'Glovebox and storage compartments',
                        'Parcel shelf / luggage cover', 'Boot trim and load floor', 'Dampness and water-ingress evidence',
                        'Interior odours', 'Pedal rubber condition',
                    ]],
                    'electrical-systems-controls' => ['Electrical Systems and Controls', [
                        'Central locking and remote keys', 'Child locks', 'Boot / tailgate opening, closing and locking', 'Window operation',
                        'Exterior mirror adjustment', 'Sunroof and roof-blind operation', 'Convertible roof operation',
                        'Seat heating and ventilation', 'Instrument display and gauges', 'Dashboard illumination',
                        'Interior and luggage-area lights', 'Headlights — dipped and main beam', 'Sidelights and daytime running lights',
                        'Rear lights and number-plate lights', 'Brake lights', 'Indicators and hazard lights', 'Reverse lights',
                        'Front and rear fog lights', 'Additional fitted lights', 'Horn', 'Front and rear wipers / washers',
                        'Headlamp washers / wipers', 'Heater blower and airflow controls', 'Heating and demisting performance',
                        'Air-conditioning operation', 'Heated screens', 'Radio, CD, media and speakers',
                        'Infotainment screen and navigation', 'USB and 12V sockets', 'Parking sensors', 'Parking / surround-view cameras',
                        'Other fitted electrical controls', 'Switches/controls (condition and operation)', 'Window locks',
                    ]],
                    'bodywork-exterior' => ['Bodywork and Exterior', [
                        'Body panel damage', 'Panel alignment and gaps', 'Paintwork condition',
                        'Visible evidence of previous repairs and repair quality', 'Exterior corrosion', 'Windscreen condition',
                        'Side and rear glass condition', 'Exterior mirror glass and housings', 'Bumper condition and security',
                        'Exterior trims, grilles and mouldings', 'Exterior door handles', 'Bonnet hinges, latch and safety catch',
                        'Boot / tailgate hinges, supports and latching', 'Fuel-filler flap condition and operation',
                        'Number plate condition and security', 'Sunroof / panoramic-roof physical condition', 'Convertible roof and seals',
                        'Convertible roof storage cover - condition and storage', 'Mud flaps — where fitted', 'Door locks operation - manual blade',
                    ]],
                    'wheels-tyres' => ['Wheel and Tyres', [
                        'Front passenger side tyre', 'Front driver side tyre', 'Rear passenger side tyre', 'Rear driver side tyre',
                        'Tyre compatibility and fitment', 'Wheel trims and centre caps', 'Spare wheel and tyre',
                        'Spare wheel carrier and security', 'Locking wheel nut key', 'Jack and wheel changing tools', 'Tyre repair kit',
                        'Alloy wheel condition (if applicable)', 'Steel wheel condition',
                    ]],
                    'engine-compartment' => ['Engine Compartment', [
                        'Engine covers and fittings', 'Engine mountings — visible condition and security', 'Engine oil level',
                        'Engine oil condition - visual check only', 'Oil filler cap and dipstick condition', 'Engine oil leaks',
                        'Auxiliary drive belt, pulleys and tensioner', 'Timing belt / cover — condition where visible without dismantling',
                        'Air-filter element — where accessible', 'Air intake and breather pipes', 'Turbo / supercharger and intercooler pipework',
                        'Vacuum hoses and connections', 'Wiring, connectors and engine earth connections', 'Visible ignition components',
                        'Evidence of modifications or makeshift repairs', 'Engine bay cleanliness',
                        'Fuel Injection Leak Off Pipe Condition (if visible)', 'Other fluid leaks',
                        'Screen wash reservoir, cap, accessible pipes and leaks',
                    ]],
                    'cooling-system' => ['Cooling System', [
                        'Coolant level', 'Coolant condition', 'Expansion tank condition and security',
                        'Radiator / expansion - tank cap and seal', 'Radiator condition and mountings', 'Coolant hoses, pipes and connections',
                        'Thermostat housing condition', 'Water-pump condition — visible leaks / damage / noise',
                        'Cooling system leak evidence', 'Cooling fan, shroud and wiring condition', 'Previous overheating evidence',
                    ]],
                    'fuel-system' => ['Fuel System', [
                        'Fuel tank condition', 'Fuel tank mountings, straps and protective shields', 'Filler neck and connecting hoses',
                        'Fuel cap / capless filler seal', 'Fuel supply and return pipes', 'Fuel filter housing and connections',
                        'Accessible fuel pumps and connections', 'Fuel rail and high pressure pipes', 'Injectors and seals',
                        'Breather pipes', 'Fuel leak evidence',
                    ]],
                    'battery-starting-charging' => ['Battery Starting and Charging', [
                        'Battery casing condition (where visible)', 'Battery mounting and security (where visible)',
                        'Battery terminals and connections (where visible)', 'Battery cables and earth straps (where visible)',
                        'Battery vent pipe — where fitted (where visible)', 'Battery specification suitability (where visible)',
                        'Battery voltage before starting', 'Starter motor operation and noise', 'Starter motor wiring and mountings',
                        'Alternator condition, connections and mountings', 'Charging performance — electrical loads off/on',
                        'Warm charging performance',
                    ]],
                    'cold-start-checks' => ['Cold Start Checks', [
                        'Cold starting performance', 'Cold engine noise', 'Cold running and idle stability', 'Exhaust smoke on cold start',
                    ]],
                    'front-suspension-steering' => ['Front Suspension and Steering', [
                        'Front springs and seating', 'Front shock absorbers / struts', 'Strut top mounts and bearings',
                        'Bump stops and suspension dust covers', 'Suspension arms, links, bushes and fixings', 'Suspension ball joints',
                        'Anti-roll bar, bushes and drop links', 'Front hubs and wheel bearings', 'Steering rack / box and mountings',
                        'Track rods and track-rod ends', 'Steering rack gaiters', 'Steering column, joints and couplings',
                        'Power steering components and leaks', 'Power steering fluid level and condition', 'Front subframe and mountings',
                        'Front air-suspension components', 'Suspension height adjustment and self-levelling',
                    ]],
                    'rear-suspension' => ['Rear Suspension', [
                        'Rear springs and seating', 'Rear shock absorbers / struts', 'Shock absorber / strut mountings',
                        'Bump stops and suspension dust covers', 'Suspension arms, links, bushes and fixings', 'Suspension ball joints',
                        'Anti-roll bar, bushes and drop links', 'Rear hubs and wheel bearings', 'Rear axle / beam and mountings',
                        'Rear subframe and mountings', 'Rear air suspension components',
                    ]],
                    'braking-system' => ['Braking System', [
                        'Front pads and discs', 'Rear pads and discs', 'Brake drums — external condition',
                        'Brake calipers, carriers and fixings', 'Brake backplates and dust shields', 'Rigid brake pipes and connections',
                        'Flexible brake hoses', 'Brake fluid level and reservoir condition',
                        'Brake master cylinder — condition, security and leaks', 'Brake servo, vacuum supply and assistance',
                        'Brake pedal and linkage — condition, travel and pressure retention',
                        'Parking-brake cables, linkages and actuators', 'ABS hydraulic unit, visible wiring and sensor connections',
                        'Brake binding / wheel rotation', 'Uneven brake wear', 'Visible brake fluid leaks',
                    ]],
                    'clutch-transmission' => ['Clutch and Transmission', [
                        'Clutch pedal and linkage', 'Clutch cable and adjuster',
                        'Clutch master cylinder, pipes, hoses and separate reservoir', 'Clutch slave cylinder — where accessible',
                        'Gear selector, cables and linkages', 'Gearbox casing, seals and leaks', 'Gearbox mountings',
                        'Transmission fluid level and condition — where accessible', 'Transmission cooler and pipework — where fitted',
                        'Front driveshafts, joints, gaiters and support bearings', 'Rear driveshafts, joints and gaiters',
                        'Propshafts, universal/sliding joints and support bearings', 'Differential casings, mountings and leaks',
                        'Transfer box / power transfer unit — condition, mountings and leaks',
                        'Four wheel drive couplings and connections — where accessible',
                    ]],
                    'underside-condition' => ['Underside Condition', [
                        'Chassis rails and structural members', 'Floorpan condition', 'Structural sills and jacking points',
                        'Wheel arches and inner wings — underside areas', 'Underbody corrosion protection',
                        'Previous structural repairs and welding', 'Undertrays, protective covers and fixings',
                        'Underbody heat shields and fixings', 'Underbody wiring, connectors and retaining clips',
                        'Towbar, mounting points and fixings — where fitted', 'Corrosion - floor/chassis', 'Engine underside leakage',
                    ]],
                    'exhaust-emissions' => ['Exhaust and Emissions System', [
                        'Exhaust manifold and accessible connections', 'Exhaust pipes and silencers', 'Exhaust joints, clamps and seals',
                        'Exhaust flexible sections', 'Exhaust mountings and rubber hangers',
                        'Exhaust clearance from surrounding components', 'Exhaust gas leaks',
                        'Catalytic converter / particulate filter — external condition', 'Exhaust sensors, wiring and pressure pipes',
                        'Visible exhaust modifications or tampering', 'Adblue level / remaining level (dashboard check)',
                        'Adblue filler, cap, tank and accessible pipes',
                    ]],
                    'road-test' => ['Road Test', [
                        'Starting system/ignition lock', 'Exhaust smoke under load', 'Engine noise under load', 'Engine response',
                        'Turbo/super charger operation and noise', 'Parking brake application - release and holding performance',
                        'Foot brake operation', 'Manual gearbox operation - gear selection and noise',
                        'Automatic shift quality - upshifts and downshifts', 'Clutch operation', 'Accelerator Pedal Operation',
                        'Road holding/stability', 'Steering wheel alignment', 'Steering response and feel',
                        'Four wheel drive operation', 'Final drive operation/noise level', 'Abnormal noises and rattles',
                    ]],
                    'warning-lights-diagnostics' => ['Warning Lights and Diagnostics', [
                        'Dashboard warning lights - ignition on', 'Dashboard warning lights and messages - engine running',
                        'Road test warnings and messages', 'Post road test diagnostic scan',
                    ]],
                    'post-road-test-checks' => ['Post Road Test Checks', [
                        'Hot restart', 'Hot idle stability', 'Engine noise when warm', 'Operating temperature',
                        'Cooling fan operation', 'Post road test leak check', 'Exhaust smoke at operating temperature',
                        'Abnormal smells', 'Post road test warning lights/messages',
                    ]],
                ],
            ],
            'bronze' => [
                'name' => 'Bronze',
                'description' => 'Bronze pre-purchase vehicle inspection checklist.',
                'base_price' => '120.00',
                'sort_order' => 3,
                'sections' => [
                    'interior-comfort' => ['Interior and Comfort', [
                        'Seat upholstery and condition', 'Seat adjustment and locking', 'Head restraints',
                        'Seat belts, buckles and retraction', 'Steering wheel condition and adjustment',
                        'Dashboard and centre console condition', 'Door trims and interior fittings',
                        'Interior handles and door release', 'Door seals, hinges and straps', 'Carpets and floor mats',
                        'Headlining', 'Interior rear-view mirror', 'Boot trim and load floor',
                        'Dampness and water-ingress evidence', 'Interior odours', 'Pedal rubber condition',
                    ]],
                    'electrical-systems-controls' => ['Electrical Systems and Controls', [
                        'Central locking and remote keys', 'Child locks', 'Boot / tailgate opening, closing and locking',
                        'Window operation', 'Exterior mirror adjustment', 'Instrument display and gauges',
                        'Dashboard illumination', 'Interior and luggage-area lights', 'Headlights — dipped and main beam',
                        'Sidelights and daytime running lights', 'Rear lights and number-plate lights', 'Brake lights',
                        'Indicators and hazard lights', 'Reverse lights', 'Front and rear fog lights', 'Horn',
                        'Front and rear wipers / washers', 'Heater blower and airflow controls',
                        'Heating and demisting performance', 'Air-conditioning operation', 'Basic radio and speaker operation',
                        'Infotainment screen basic operation', 'Switches/controls (condition and operation)', 'Window locks',
                    ]],
                    'bodywork-exterior' => ['Bodywork and Exterior', [
                        'Body panel damage', 'Obvious panel misalignment - visual check', 'Paintwork condition',
                        'Obvious visible evidence of previous repairs', 'Exterior corrosion', 'Windscreen condition',
                        'Side and rear glass condition', 'Exterior mirror glass and housings', 'Bumper condition and security',
                        'Exterior trims, grilles and mouldings', 'Exterior door handles', 'Bonnet hinges, latch and safety catch',
                        'Boot / tailgate hinges, supports and latching', 'Fuel-filler flap condition and operation',
                        'Number plate condition and security', 'Sunroof / panoramic-roof physical condition', 'Convertible roof and seals',
                    ]],
                    'wheels-tyres' => ['Wheel and Tyres', [
                        'Front passenger side tyre', 'Front driver side tyre', 'Rear passenger side tyre', 'Rear driver side tyre',
                        'Tyre compatibility and fitment', 'Spare wheel and tyre - where accessible without removal',
                        'Locking wheel nut key', 'Alloy wheel condition (if applicable)', 'Steel wheel condition',
                    ]],
                    'engine-compartment' => ['Engine Compartment', [
                        'Engine covers and fittings', 'Engine mountings — visible condition and security', 'Engine oil level',
                        'Engine oil condition - visual check only', 'Oil filler cap and dipstick condition', 'Engine oil leaks',
                        'Auxiliary drive belt - visible condition', 'Air intake and breather pipes',
                        'Visual turbo / supercharger pipework - obvious damage or leaks',
                        'Evidence of modifications or makeshift repairs', 'Visible fuel leak evidence', 'Other fluid leaks',
                        'Screenwash level and visible reservoir condition',
                    ]],
                    'cooling-system' => ['Cooling System', [
                        'Coolant level', 'Coolant condition', 'Expansion tank condition and security',
                        'Radiator / expansion - tank cap and seal', 'Radiator condition and mountings',
                        'Coolant hoses, pipes and connections', 'Cooling system leak evidence',
                        'Cooling fan, shroud and wiring condition', 'Previous overheating evidence',
                    ]],
                    'fuel-system' => ['Fuel System', [
                        'Fuel tank condition', 'Fuel cap / capless filler seal', 'Visible fuel pipes and connections',
                        'Fuel leak evidence',
                    ]],
                    'battery-starting-charging' => ['Battery Starting and Charging', [
                        'Battery casing condition (where visible)', 'Battery mounting and security (where visible)',
                        'Battery terminals and connections (where visible)', 'Battery cables and earth straps (where visible)',
                        'Starter motor operation and noise', 'Basic charging voltage check',
                    ]],
                    'cold-start-checks' => ['Cold Start Checks', [
                        'Cold starting performance', 'Cold engine noise', 'Cold running and idle stability', 'Exhaust smoke on cold start',
                    ]],
                    'front-suspension-steering' => ['Front Suspension and Steering', [
                        'Front springs and seating', 'Front shock absorbers / struts',
                        'Suspension arms, links, bushes and fixings', 'Power steering components and leaks',
                        'Power steering fluid level and condition',
                    ]],
                    'rear-suspension' => ['Rear Suspension', [
                        'Rear springs and seating', 'Rear shock absorbers / struts',
                        'Suspension arms, links, bushes and fixings',
                    ]],
                    'braking-system' => ['Braking System', [
                        'Front pads and discs', 'Rear pads and discs', 'Rigid brake pipes and connections',
                        'Flexible brake hoses', 'Brake fluid level and reservoir condition', 'Brake servo, vacuum supply and assistance',
                        'Brake pedal and linkage — condition, travel and pressure retention', 'Visible brake fluid leaks',
                    ]],
                    'clutch-transmission' => ['Clutch and Transmission', [
                        'Clutch pedal and linkage', 'Gear selector, cables and linkages', 'Gearbox casing, seals and leaks',
                        'Transmission fluid level and condition — where accessible',
                    ]],
                    'underside-condition' => ['Underside Condition', [
                        'Chassis rails and structural members', 'Floorpan condition', 'Structural sills and jacking points',
                        'Wheel arches and inner wings — underside areas', 'Corrosion - floor/chassis', 'Engine underside leakage',
                    ]],
                    'exhaust-emissions' => ['Exhaust and Emissions System', [
                        'Exhaust pipes and silencers', 'Exhaust mountings and rubber hangers', 'Exhaust gas leaks',
                        'Visible exhaust modifications or tampering', 'Adblue level / remaining level (dashboard check)',
                    ]],
                    'road-test' => ['Road Test', [
                        'Starting system/ignition lock', 'Exhaust smoke under load', 'Engine noise under load', 'Engine response',
                        'Parking brake application - release and holding performance', 'Foot brake operation',
                        'Manual gearbox operation - gear selection and noise',
                        'Automatic shift quality - upshifts and downshifts', 'Clutch operation', 'Accelerator Pedal Operation',
                        'Road holding/stability', 'Steering wheel alignment', 'Steering response and feel', 'Abnormal noises and rattles',
                    ]],
                    'warning-lights-diagnostics' => ['Warning Lights and Diagnostics', [
                        'Dashboard warning lights - ignition on', 'Dashboard warning lights and messages - engine running',
                        'Road test warnings and messages', 'Post road test diagnostic scan',
                    ]],
                    'post-road-test-checks' => ['Post Road Test Checks', [
                        'Restart after road test', 'Hot idle stability', 'Engine noise when warm', 'Operating temperature',
                        'Post road test leak check', 'Exhaust smoke at operating temperature', 'Abnormal smells',
                        'Post road test warning lights/messages',
                    ]],
                ],
            ],
        ];

        DB::transaction(function () use ($catalogues): void {
            $localPackagesAreActive = app()->environment('local');

            foreach ($catalogues as $packageCode => $catalogue) {
                $packageId = $this->upsertId('packages', ['code' => $packageCode], [
                    'name' => $catalogue['name'],
                    'description' => $catalogue['description'],
                    'base_price' => $catalogue['base_price'],
                    'currency' => 'GBP',
                    'active' => $localPackagesAreActive,
                    'sort_order' => $catalogue['sort_order'],
                    'video_included' => false,
                    'is_demo' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);

                $featureId = $this->upsertId('inspection_features', ['code' => $packageCode.'-full-inspection'], [
                    'name' => $catalogue['name'],
                    'description' => $catalogue['description'],
                    'addon_price' => null,
                    'addon_enabled' => false,
                    'active' => true,
                    'sort_order' => $catalogue['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);

                DB::table('package_features')->updateOrInsert(
                    ['package_id' => $packageId, 'feature_id' => $featureId],
                    ['included' => true, 'sort_order' => 1]
                );
                DB::table('feature_questions')->where('feature_id', $featureId)->delete();

                $sectionSort = 0;
                $questionSort = 0;
                foreach ($catalogue['sections'] as $sectionCode => [$title, $prompts]) {
                    $sectionSort++;
                    $sectionId = $this->upsertId('inspection_sections', ['code' => $packageCode.'-'.$sectionCode], [
                        'title' => $title,
                        'sort_order' => $sectionSort,
                        'active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);

                    foreach ($prompts as $promptSort => $prompt) {
                        $questionSort++;
                        $questionId = $this->upsertId('questions', [
                            'code' => $packageCode.'-'.$sectionCode.'-'.str_pad((string) ($promptSort + 1), 3, '0', STR_PAD_LEFT),
                        ], [
                            'section_id' => $sectionId,
                            'prompt' => $prompt,
                            'response_type' => 'rating',
                            'required' => false,
                            'sort_order' => $promptSort + 1,
                            'active' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]);

                        DB::table('feature_questions')->updateOrInsert(
                            ['feature_id' => $featureId, 'question_id' => $questionId],
                            ['sort_order' => $questionSort]
                        );
                    }
                }
            }
        });
    }

    private function upsertId(string $table, array $where, array $values): int
    {
        $id = DB::table($table)->where($where)->value('id');
        if ($id) {
            DB::table($table)->where($where)->update($values);

            return (int) $id;
        }

        return (int) DB::table($table)->insertGetId($where + $values);
    }
}
