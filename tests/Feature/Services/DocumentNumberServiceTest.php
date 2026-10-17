<?php

namespace Tests\Feature\Services;

use App\Enums\LocationType;
use App\Models\Location;
use App\Services\DocumentNumberService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_numbers_are_sequential_per_day_per_location(): void
    {
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $service = app(DocumentNumberService::class);

        $first = $service->next('sale', $location->id);
        $second = $service->next('sale', $location->id);

        $period = now()->format('ymd');

        $this->assertSame("PJ-TKO-{$period}-0001", $first);
        $this->assertSame("PJ-TKO-{$period}-0002", $second);
    }

    public function test_numbers_are_independent_per_location(): void
    {
        $toko = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $gudang = Location::create(['code' => 'GDG', 'name' => 'Gudang', 'type' => LocationType::Warehouse]);
        $service = app(DocumentNumberService::class);

        $service->next('sale', $toko->id);
        $first = $service->next('sale', $gudang->id);

        $period = now()->format('ymd');

        $this->assertSame("PJ-GDG-{$period}-0001", $first);
    }

    public function test_prefix_can_be_overridden_via_setting(): void
    {
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        setting_set('doc.prefix.sale', 'INV');

        $number = app(DocumentNumberService::class)->next('sale', $location->id);

        $this->assertStringStartsWith('INV-TKO-', $number);
    }
}
