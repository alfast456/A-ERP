<?php

namespace Tests\Feature;

use App\Enums\YarnTransactionType;
use App\Models\FactoryArea;
use App\Models\User;
use App\Models\YarnStockBalance;
use App\Models\YarnTransaction;
use Tests\TestCase;

class YarnFloorStockTest extends TestCase
{
    private function getStockBalance(): YarnStockBalance
    {
        $area = FactoryArea::first();

        return YarnStockBalance::first() ?? YarnStockBalance::create([
            'factory_area_id' => $area->id,
            'yarn_code' => 'TC-30S-' . rand(100, 999),
            'yarn_name' => 'TC 30s Carded Natural',
            'color_name' => 'Natural White',
            'lot_number' => 'LOT-' . rand(1000, 9999),
            'qty_cones' => 100,
            'weight_kg' => 250.00,
        ]);
    }

    public function test_yarn_index_route_is_accessible(): void
    {
        $response = $this->get(route('yarn.index'));
        $response->assertOk();
        $response->assertSee('Saldo & Logistik Benang Lantai Rajut', false);
    }

    public function test_yarn_transactions_route_is_accessible(): void
    {
        $response = $this->get(route('yarn.transactions'));
        $response->assertOk();
        $response->assertSee('Riwayat Transaksi & Mutasi Benang', false);
    }

    public function test_store_yarn_transaction_receipt(): void
    {
        $balance = $this->getStockBalance();
        $initialCones = $balance->qty_cones;
        $initialWeight = (float) $balance->weight_kg;

        $ref = 'SJ-TEST-' . rand(1000, 9999);
        $data = [
            'yarn_stock_balance_id' => $balance->id,
            'transaction_type' => 'RECEIPT_FROM_WH',
            'cones_delta' => 20,
            'weight_kg_delta' => 50.50,
            'reference_number' => $ref,
        ];

        $response = $this->post(route('yarn.transactions.store'), $data);
        $response->assertRedirect(route('yarn.transactions'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('yarn_transactions', [
            'yarn_stock_balance_id' => $balance->id,
            'transaction_type' => 'RECEIPT_FROM_WH',
            'reference_number' => $ref,
            'cones_delta' => 20,
        ]);

        $balance->refresh();
        $this->assertEquals($initialCones + 20, $balance->qty_cones);
        $this->assertEqualsWithDelta($initialWeight + 50.50, (float) $balance->weight_kg, 0.01);
    }

    public function test_store_yarn_transaction_usage_deducts_stock(): void
    {
        $balance = $this->getStockBalance();
        $initialCones = $balance->qty_cones;
        $initialWeight = (float) $balance->weight_kg;

        $ref = 'USAGE-TEST-' . rand(1000, 9999);
        $data = [
            'yarn_stock_balance_id' => $balance->id,
            'transaction_type' => 'USAGE_KNITTING',
            'cones_delta' => 10,
            'weight_kg_delta' => 25.00,
            'reference_number' => $ref,
        ];

        $response = $this->post(route('yarn.transactions.store'), $data);
        $response->assertRedirect(route('yarn.transactions'));

        $this->assertDatabaseHas('yarn_transactions', [
            'yarn_stock_balance_id' => $balance->id,
            'transaction_type' => 'USAGE_KNITTING',
            'cones_delta' => -10,
        ]);

        $balance->refresh();
        $this->assertEquals(max(0, $initialCones - 10), $balance->qty_cones);
        $this->assertEqualsWithDelta(max(0, $initialWeight - 25.00), (float) $balance->weight_kg, 0.01);
    }

    public function test_yarn_requirements_route_is_accessible(): void
    {
        $response = $this->get(route('yarn.requirements'));
        $response->assertOk();
        $response->assertSee('Estimasi Kebutuhan Benang per PO');
        $response->assertSee('Ringkasan Stok Benang Ready di Lantai');
    }
}
