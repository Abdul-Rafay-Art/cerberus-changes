<?php

namespace Tests\Feature;

use App\Models\Package;
use Database\Seeders\SilverBronzeChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SilverBronzeCatalogueSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_silver_and_bronze_seeders_match_the_downloaded_checklists_and_keep_prices(): void
    {
        $this->seed(SilverBronzeChecklistSeeder::class);

        $silver = Package::query()->where('code', 'silver')->with('includedFeatures.questions')->firstOrFail();
        $bronze = Package::query()->where('code', 'bronze')->with('includedFeatures.questions')->firstOrFail();
        $silverPrompts = $silver->includedFeatures->flatMap(fn ($feature) => $feature->questions)->pluck('prompt');
        $bronzePrompts = $bronze->includedFeatures->flatMap(fn ($feature) => $feature->questions)->pluck('prompt');

        $this->assertSame('170.00', $silver->base_price);
        $this->assertSame('120.00', $bronze->base_price);
        $this->assertSame('Silver', $silver->name);
        $this->assertSame('Bronze', $bronze->name);
        $this->assertFalse($silver->active);
        $this->assertFalse($bronze->active);
        $this->assertTrue($silverPrompts->contains('Rear-seat folding and locking'));
        $this->assertTrue($silverPrompts->contains('Post road test diagnostic scan'));
        $this->assertFalse($silverPrompts->contains('Pre road test diagnostic scan'));
        $this->assertTrue($bronzePrompts->contains('Basic charging voltage check'));
        $this->assertTrue($bronzePrompts->contains('Post road test warning lights/messages'));
    }
}
