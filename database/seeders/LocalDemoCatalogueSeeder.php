<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalDemoCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function (): void {
            $demoQuestions = [
                'demo-exterior-condition' => 'DEMO — General exterior condition',
                'demo-interior-condition' => 'DEMO — General interior condition',
                'demo-start-and-idle' => 'DEMO — Engine starts and idles',
                'demo-warning-lights' => 'DEMO — Dashboard warning lights',
                'demo-tyres' => 'DEMO — Tyres and visible wheel condition',
                'demo-road-test' => 'DEMO — General road-test observations',
            ];

            $sectionId = DB::table('inspection_sections')->where('code', 'demo-sample-checks')->value('id');
            if (! $sectionId) {
                $sectionId = DB::table('inspection_sections')->insertGetId([
                    'code' => 'demo-sample-checks',
                    'title' => 'DEMO ONLY — Sample checks (replace with approved catalogue)',
                    'description' => 'Local preview fixture; not a Cerberus-approved inspection checklist.',
                    'sort_order' => 999,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $questionIds = [];
            foreach ($demoQuestions as $code => $prompt) {
                DB::table('questions')->updateOrInsert(
                    ['code' => $code],
                    [
                        'section_id' => $sectionId,
                        'prompt' => $prompt,
                        'help_text' => 'DEMO ONLY — sample wording for local workflow testing.',
                        'response_type' => 'rating',
                        'required' => false,
                        'sort_order' => count($questionIds) + 1,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $questionIds[] = DB::table('questions')->where('code', $code)->value('id');
            }

            $basicFeatureId = DB::table('inspection_features')->where('code', 'local-demo-basic-checks')->value('id');
            if (! $basicFeatureId) {
                $basicFeatureId = DB::table('inspection_features')->insertGetId([
                    'code' => 'local-demo-basic-checks',
                    'name' => 'DEMO ONLY — Basic sample checklist',
                    'description' => 'Local preview fixture; replace with client-approved feature mapping.',
                    'addon_enabled' => false,
                    'active' => true,
                    'sort_order' => 900,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($questionIds as $index => $questionId) {
                DB::table('feature_questions')->updateOrInsert(
                    ['feature_id' => $basicFeatureId, 'question_id' => $questionId],
                    ['sort_order' => $index + 1]
                );
            }

            $scanFeatureId = DB::table('inspection_features')->where('code', 'local-demo-diagnostic-addon')->value('id');
            if (! $scanFeatureId) {
                $scanFeatureId = DB::table('inspection_features')->insertGetId([
                    'code' => 'local-demo-diagnostic-addon',
                    'name' => 'DEMO ONLY — Diagnostic scan add-on',
                    'description' => 'Preview-only add-on and price. Not approved for customer quotations.',
                    'addon_price' => '19.95',
                    'addon_enabled' => true,
                    'active' => true,
                    'sort_order' => 901,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $scanQuestionId = DB::table('questions')->where('code', 'demo-diagnostic-scan')->value('id');
            if (! $scanQuestionId) {
                $scanQuestionId = DB::table('questions')->insertGetId([
                    'section_id' => $sectionId,
                    'code' => 'demo-diagnostic-scan',
                    'prompt' => 'DEMO — Diagnostic scan summary',
                    'help_text' => 'Local preview fixture only.',
                    'response_type' => 'rating',
                    'required' => false,
                    'sort_order' => 7,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('feature_questions')->updateOrInsert(
                ['feature_id' => $scanFeatureId, 'question_id' => $scanQuestionId],
                ['sort_order' => 1]
            );

            $goldPackageId = DB::table('packages')->where('code', 'gold')->value('id');
            $goldFeatureId = DB::table('inspection_features')->where('code', 'gold-full-inspection')->value('id');
            DB::table('packages')->where('id', $goldPackageId)->update([
                'name' => 'Gold',
                'description' => 'Full pre-purchase vehicle inspection based on the approved Cerberus Gold checklist.',
                'base_price' => '249.00',
                'active' => true,
                'is_demo' => true,
                'sort_order' => 3,
                'updated_at' => now(),
            ]);
            DB::table('package_features')->updateOrInsert(
                ['package_id' => $goldPackageId, 'feature_id' => $goldFeatureId],
                ['included' => true, 'sort_order' => 1]
            );

            DB::table('packages')->whereIn('code', ['silver', 'bronze'])->update([
                'active' => true,
                'is_demo' => false,
                'updated_at' => now(),
            ]);
        });
    }
}
