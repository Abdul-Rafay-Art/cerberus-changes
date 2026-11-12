<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SilverBronzeChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $catalogues = [
            'silver' => [
                'name' => 'Cerberus Silver Pre-Purchase Vehicle Inspection',
                'description' => 'Silver pre-purchase vehicle inspection checklist.',
                'sections' => [
                    'interior-comfort' => ['Interior and Comfort', ['Seat upholstery and condition', 'Seat adjustment and locking', 'Head restraints', 'Seat belts, buckles and retraction', 'Steering wheel condition and adjustment', 'Dashboard and centre console condition', 'Door trims and interior fittings', 'Interior handles and door release', 'Carpets and floor mats', 'Headlining', 'Interior rear-view mirror', 'Boot trim and load floor', 'Dampness and water-ingress evidence', 'Interior odours']],
                    'electrical-systems-controls' => ['Electrical Systems and Controls', ['Central locking and remote keys', 'Window operation', 'Exterior mirror adjustment, folding and heating', 'Instrument display and gauges', 'Dashboard illumination and dimming', 'Interior and luggage-area lights', 'Headlights — dipped and main beam', 'Sidelights and daytime running lights', 'Rear lights and number-plate lights', 'Brake lights', 'Indicators and hazard lights', 'Reverse lights', 'Front and rear fog lights', 'Horn', 'Front and rear wipers / washers', 'Heater blower and airflow controls', 'Heating and demisting performance', 'Air-conditioning operation and vent temperature', 'Radio, CD, media and speakers', 'Infotainment screen and navigation', 'Parking sensors', 'Switches/controls (condition & operation)']],
                    'bodywork-exterior' => ['Bodywork and Exterior', ['Body panel damage', 'Panel alignment and gaps', 'Paintwork condition', 'Previous repair evidence and quality', 'Exterior corrosion', 'Windscreen condition', 'Side and rear glass condition', 'Exterior mirror glass and housings', 'Bumper condition and security', 'Exterior trims, grilles and mouldings', 'Exterior door handles', 'Bonnet hinges, latch and safety catch', 'Boot / tailgate hinges, supports and latching', 'Fuel-filler flap condition and operation', 'Number plate condition and security']],
                    'wheels-tyres' => ['Wheels and Tyres', ['Front passenger side tyre', 'Front driver side tyre', 'Rear passenger side tyre', 'Rear driver side tyre', 'Tyre compatibility and fitment', 'Spare wheel and tyre', 'Locking wheel nut key', 'Jack and wheel changing tools', 'Tyre repair kit', 'Alloy wheel condition (if applicable)', 'Steel wheel condition']],
                    'engine-compartment' => ['Engine Compartment', ['Engine covers and fittings', 'Engine oil level', 'Engine oil condition', 'Oil filler cap and dipstick condition', 'Engine oil leaks', 'Auxiliary drive belt, pulleys and tensioner', 'Air intake and breather pipes', 'Vacuum hoses and connections', 'Wiring, connectors and engine earth connections', 'Evidence of modifications or makeshift repairs', 'Engine bay cleanliness', 'Other fluid leaks', 'Screen wash reservoir, cap, accessible pipes and leaks']],
                    'cooling-system' => ['Cooling System', ['Coolant level', 'Coolant condition', 'Expansion tank condition and security', 'Radiator condition and mountings', 'Coolant hoses, pipes and connections', 'Cooling system leak evidence', 'Cooling fan, shroud and wiring condition', 'Previous overheating evidence']],
                    'fuel-system' => ['Fuel System', ['Fuel cap / capless filler seal', 'Fuel supply and return pipes', 'Fuel leak evidence']],
                    'battery-starting-charging' => ['Battery, Starting and Charging', ['Battery casing condition', 'Battery mounting and security', 'Battery terminals and connections', 'Battery cables and earth straps', 'Battery voltage before starting', 'Battery test and report', 'Starter motor operation and noise', 'Charging performance — electrical loads off/on']],
                    'cold-start-checks' => ['Cold Start Checks', ['Cold starting performance', 'Cold engine noise', 'Cold running and idle stability', 'Exhaust smoke on cold start']],
                    'front-suspension-steering' => ['Front Suspension and Steering', ['Front springs and seating', 'Front shock absorbers / struts', 'Suspension arms, links, bushes and fixings', 'Suspension ball joints', 'Anti-roll bar, bushes and drop links', 'Front hubs and wheel bearings', 'Steering rack / box and mountings', 'Track rods and track-rod ends', 'Power steering components and leaks']],
                    'rear-suspension' => ['Rear Suspension', ['Rear springs and seating', 'Rear shock absorbers / struts', 'Suspension arms, links, bushes and fixings', 'Anti-roll bar, bushes and drop links', 'Rear hubs and wheel bearings']],
                    'braking-system' => ['Braking System', ['Front pads and discs', 'Rear pads and discs', 'Brake calipers, carriers and fixings', 'Rigid brake pipes and connections', 'Flexible brake hoses', 'Brake fluid level and reservoir condition', 'Brake pedal and linkage — condition, travel and pressure retention', 'Parking-brake cables, linkages and actuators', 'Uneven brake wear', 'Visible brake fluid leaks']],
                    'clutch-transmission' => ['Clutch and Transmission', ['Clutch pedal and linkage', 'Gear selector, cables and linkages', 'Gearbox casing, seals and leaks', 'Gearbox mountings', 'Front driveshafts, joints, gaiters and support bearings', 'Rear driveshafts, joints and gaiters']],
                    'underside-condition' => ['Underside Condition', ['Chassis rails and structural members', 'Floorpan condition', 'Structural sills and jacking points', 'Wheel arches and inner wings — underside areas', 'Previous structural repairs and welding', 'Corrosion - floor/chassis', 'Engine underside leakage']],
                    'exhaust-emissions' => ['Exhaust and Emissions System', ['Exhaust pipes and silencers', 'Exhaust joints, clamps and seals', 'Exhaust mountings and rubber hangers', 'Exhaust gas leaks']],
                    'road-test' => ['Road Test', ['Starting system/ignition lock', 'Exhaust smoke under load', 'Engine noise under load', 'Engine response', 'Parking brake application - release and holding performance', 'Foot brake operation', 'Manual gearbox operation - gear selection and noise', 'Automatic shift quality - upshifts and downshifts', 'Clutch operation', 'Accelerator Pedal Operation', 'Road holding/stability', 'Steering wheel alignment', 'Steering response and feel', 'Abnormal noises and rattles']],
                    'warning-lights-diagnostics' => ['Warning Lights and Diagnostics', ['Dashboard warning lights - ignition on', 'Dashboard warning lights and messages - engine running', 'Road test warnings and messages', 'Pre road test diagnostic scan']],
                ],
            ],
            'bronze' => [
                'name' => 'Cerberus Bronze Pre-Purchase Vehicle Inspection',
                'description' => 'Bronze pre-purchase vehicle inspection checklist.',
                'sections' => [
                    'interior-comfort' => ['Interior and Comfort', ['Seat upholstery and condition', 'Seat adjustment and locking', 'Seat belts, buckles and retraction', 'Steering wheel condition and adjustment', 'Dashboard and centre console condition', 'Interior handles and door release', 'Dampness and water-ingress evidence']],
                    'electrical-systems-controls' => ['Electrical Systems and Controls', ['Central locking and remote keys', 'Window operation', 'Instrument display and gauges', 'Headlights — dipped and main beam', 'Brake lights', 'Indicators and hazard lights', 'Horn', 'Front and rear wipers / washers', 'Air-conditioning operation and vent temperature']],
                    'bodywork-exterior' => ['Bodywork and Exterior', ['Body panel damage', 'Panel alignment and gaps', 'Paintwork condition', 'Exterior corrosion', 'Windscreen condition', 'Side and rear glass condition', 'Bumper condition and security']],
                    'wheels-tyres' => ['Wheels and Tyres', ['Front passenger side tyre', 'Front driver side tyre', 'Rear passenger side tyre', 'Rear driver side tyre', 'Spare wheel and tyre']],
                    'engine-compartment' => ['Engine Compartment', ['Engine oil level', 'Engine oil condition', 'Engine oil leaks', 'Other fluid leaks']],
                    'cooling-system' => ['Cooling System', ['Coolant level', 'Coolant condition', 'Cooling system leak evidence']],
                    'battery-starting-charging' => ['Battery, Starting and Charging', ['Battery casing condition', 'Battery terminals and connections', 'Battery voltage before starting']],
                    'braking-system' => ['Braking System', ['Front pads and discs', 'Rear pads and discs', 'Brake fluid level and reservoir condition', 'Visible brake fluid leaks']],
                    'road-test' => ['Road Test', ['Starting system/ignition lock', 'Engine noise under load', 'Engine response', 'Foot brake operation', 'Road holding/stability', 'Steering response and feel', 'Abnormal noises and rattles']],
                    'warning-lights-diagnostics' => ['Warning Lights and Diagnostics', ['Dashboard warning lights - ignition on', 'Dashboard warning lights and messages - engine running']],
                ],
            ],
        ];

        DB::transaction(function () use ($catalogues): void {
            $upsertId = static function (string $table, array $where, array $values): int {
                $id = DB::table($table)->where($where)->value('id');
                if ($id) {
                    DB::table($table)->where($where)->update($values);

                    return (int) $id;
                }

                return (int) DB::table($table)->insertGetId($where + $values);
            };

            foreach ($catalogues as $packageCode => $catalogue) {
                $packageId = $upsertId('packages',
                    ['code' => $packageCode],
                    ['name' => $catalogue['name'], 'description' => $catalogue['description'], 'base_price' => $packageCode === 'silver' ? 170 : 120, 'currency' => 'GBP', 'active' => false, 'sort_order' => $packageCode === 'silver' ? 2 : 3, 'video_included' => false, 'is_demo' => false, 'updated_at' => now(), 'created_at' => now()]
                );
                $featureId = $upsertId('inspection_features',
                    ['code' => $packageCode.'-full-inspection'],
                    ['name' => $catalogue['name'], 'description' => $catalogue['description'], 'addon_price' => null, 'addon_enabled' => false, 'active' => true, 'sort_order' => $packageCode === 'silver' ? 2 : 3, 'updated_at' => now(), 'created_at' => now()]
                );
                DB::table('package_features')->updateOrInsert(['package_id' => $packageId, 'feature_id' => $featureId], ['included' => true, 'sort_order' => 1]);

                foreach ($catalogue['sections'] as $sectionOrder => [$title, $prompts]) {
                    $sectionId = $upsertId('inspection_sections',
                        ['code' => $packageCode.'-'.$sectionOrder],
                        ['title' => $title, 'sort_order' => array_search($sectionOrder, array_keys($catalogue['sections']), true) + 1, 'active' => true, 'updated_at' => now(), 'created_at' => now()]
                    );
                    foreach ($prompts as $index => $prompt) {
                        $questionId = $upsertId('questions',
                            ['code' => $packageCode.'-'.Str::slug($sectionOrder).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                            ['section_id' => $sectionId, 'prompt' => $prompt, 'response_type' => 'rating', 'required' => false, 'sort_order' => $index + 1, 'active' => true, 'updated_at' => now(), 'created_at' => now()]
                        );
                        DB::table('feature_questions')->updateOrInsert(['feature_id' => $featureId, 'question_id' => $questionId], ['sort_order' => ($index + 1)]);
                    }
                }
            }
        });
    }
}
