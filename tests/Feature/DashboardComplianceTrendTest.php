<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardComplianceTrendTest extends TestCase
{
    use DatabaseTransactions;

    protected function getLogistikUser(): User
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        return $user;
    }

    public function test_compliance_trend_default_properties_and_rendered_elements(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'compliance')
            ->assertSet('complianceTrendPeriod', 14)
            ->assertSee('Tren Kepatuhan Laporan Harian')
            ->assertSee('chart-compliance-trend')
            ->assertSee('data-compliance')
            ->assertDispatched('charts-updated');
    }

    public function test_compliance_trend_switches_period(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'compliance')
            ->assertSet('complianceTrendPeriod', 14)
            ->call('setComplianceTrendPeriod', 7)
            ->assertSet('complianceTrendPeriod', 7)
            ->call('setComplianceTrendPeriod', 30)
            ->assertSet('complianceTrendPeriod', 30)
            ->call('setComplianceTrendPeriod', 99) // Invalid period, unchanged
            ->assertSet('complianceTrendPeriod', 30)
            ->call('resetComplianceFilters')
            ->assertSet('complianceTrendPeriod', 14);
    }

    public function test_calculate_compliance_trend_aggregates_daily_submissions(): void
    {
        $distA = Distributor::create([
            'distributor_code' => 'TEST-COMP-A',
            'name' => 'Distributor Test A',
            'is_active' => true,
        ]);

        $distB = Distributor::create([
            'distributor_code' => 'TEST-COMP-B',
            'name' => 'Distributor Test B',
            'is_active' => true,
        ]);

        $itemA = DistributorItem::create([
            'distributor_id' => $distA->id,
            'item_code' => 'ITEM-A',
            'item_name' => 'Produk A',
        ]);

        $targetDate = '2026-09-10';

        // Dist A uploads on 2026-09-09 and 2026-09-10
        StockEntry::create([
            'distributor_id' => $distA->id,
            'distributor_item_id' => $itemA->id,
            'tanggal' => '2026-09-09',
            'satuan' => 'BTL',
            'quantity' => 100,
        ]);
        StockEntry::create([
            'distributor_id' => $distA->id,
            'distributor_item_id' => $itemA->id,
            'tanggal' => '2026-09-10',
            'satuan' => 'BTL',
            'quantity' => 150,
        ]);

        $dashboard = new Dashboard();
        $dashboard->complianceTrendPeriod = 7;

        $trend = $dashboard->calculateComplianceTrend([$distA->id, $distB->id], $targetDate);

        $this->assertEquals(7, count($trend['labels']));
        $this->assertEquals(2, $trend['total_branches']);
        $this->assertTrue($trend['has_data']);

        // Check index for targetDate (2026-09-10) which is the last day
        $lastIdx = 6;
        $this->assertEquals('10 Sep', $trend['labels'][$lastIdx]);
        $this->assertEquals(1, $trend['submitted'][$lastIdx]); // only Dist A submitted
        $this->assertEquals(1, $trend['missing'][$lastIdx]);   // Dist B did not submit
        $this->assertEquals(50.0, $trend['rates'][$lastIdx]);  // 1 / 2 = 50.0%
    }
}
