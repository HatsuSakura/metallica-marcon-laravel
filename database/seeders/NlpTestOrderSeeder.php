<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NlpTestOrderSeeder extends Seeder
{
    // cer_code_id => [category, is_dangerous]
    private const CER_SUBSET = [
        67  => ['ferrosi',    false],
        99  => ['ferrosi',    false],
        102 => ['ferrosi',    true],
        72  => ['RAEE',       true],
        73  => ['RAEE',       false],
        75  => ['RAEE',       false],
        1   => ['plastici',   false],
        69  => ['plastici',   false],
        117 => ['plastici',   false],
        61  => ['bombolette', true],
        80  => ['batterie',   true],
        82  => ['batterie',   false],
    ];

    // Probability of is_bulk = true per category
    private const BULK_PROBABILITY = [
        'ferrosi'    => 0.60,
        'RAEE'       => 0.10,
        'plastici'   => 0.50,
        'bombolette' => 0.00,
        'batterie'   => 0.00,
    ];

    // holder_id => avg weight of contents (kg)
    private const HOLDER_MAP = [
        'ferrosi'    => [2 => 150, 3 => 500, 4 => 80, 5 => 200],
        'RAEE'       => [1 => 30,  2 => 150, 4 => 80],
        'plastici'   => [2 => 150, 3 => 500, 4 => 80],
        'bombolette' => [4 => 80,  8 => 25],
        'batterie'   => [6 => 40,  7 => 15],
    ];

    private const STATUS_WEIGHTS = [
        'creato'      => 40,
        'pronto'      => 30,
        'pianificato' => 20,
        'eseguito'    => 8,
        'chiuso'      => 2,
    ];

    private const CUSTOM_DIMS = [60, 70, 80, 90, 100, 110, 120];

    public function run(): void
    {
        $now = Carbon::now();
        $cerIds = array_keys(self::CER_SUBSET);

        $customers = DB::table('customers')
            ->whereNull('deleted_at')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('sites')
                    ->whereColumn('sites.customer_id', 'customers.id')
                    ->whereNull('sites.deleted_at')
                    ->whereNotNull('sites.latitude')
                    ->whereNotNull('sites.longitude');
            })
            ->inRandomOrder()
            ->limit(500)
            ->pluck('id');

        $this->command->getOutput()->progressStart($customers->count());

        foreach ($customers as $customerId) {
            $site = DB::table('sites')
                ->where('customer_id', $customerId)
                ->whereNull('deleted_at')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderByDesc('is_main')
                ->first();

            if (!$site) {
                $this->command->getOutput()->progressAdvance();
                continue;
            }

            $orderCount = random_int(1, 2);

            for ($o = 0; $o < $orderCount; $o++) {
                $requestedAt = Carbon::now()->subDays(random_int(1, 90));
                $expectedAt  = (clone $requestedAt)->addDays(random_int(7, 45));

                $orderId = DB::table('orders')->insertGetId([
                    'customer_id'          => $customerId,
                    'site_id'              => $site->id,
                    'status'               => $this->weightedRandom(self::STATUS_WEIGHTS),
                    'is_urgent'            => random_int(1, 100) <= 15 ? 1 : 0,
                    'requested_at'         => $requestedAt,
                    'expected_withdraw_at' => $expectedAt,
                    'documents_status'     => 'not_generated',
                    'documents_version'    => 0,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);

                $itemCount = random_int(1, 4);

                for ($i = 0; $i < $itemCount; $i++) {
                    $cerCodeId = $cerIds[array_rand($cerIds)];
                    [$category, $isDangerous] = self::CER_SUBSET[$cerCodeId];
                    $isBulk = $this->rollBulk($category);

                    $item = [
                        'order_id'                  => $orderId,
                        'cer_code_id'               => $cerCodeId,
                        'is_bulk'                   => $isBulk ? 1 : 0,
                        'has_adr'                   => $isDangerous ? 1 : 0,
                        'adr'                       => $isDangerous ? 1 : 0,
                        'status'                    => 'creato',
                        'updated_by_user_id'        => 1,
                        'is_warehouse_added'        => 0,
                        'is_not_found'              => 0,
                        'has_non_conformity'        => 0,
                        'has_exploded_children'     => 0,
                        'has_selection'             => 0,
                        'is_transshipment'          => 0,
                        'is_machinery_time_manual'  => 0,
                        'is_holder_dirty'           => 0,
                        'is_holder_broken'          => 0,
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ];

                    if ($isBulk) {
                        $weightDeclared = random_int(15, 100) * 10;
                        $item['weight_declared'] = $weightDeclared;
                    } else {
                        $holderMap = self::HOLDER_MAP[$category];
                        $holderIds = array_keys($holderMap);
                        $holderId  = $holderIds[array_rand($holderIds)];
                        $avgWeight = $holderMap[$holderId];
                        $maxQty    = in_array($category, ['batterie', 'bombolette']) ? 5 : 10;
                        $qty       = random_int(1, $maxQty);
                        $variation = (80 + random_int(0, 40)) / 100; // 0.80–1.20
                        $weightDeclared = round($qty * $avgWeight * $variation, 1);

                        $item['holder_id']       = $holderId;
                        $item['holder_quantity'] = $qty;
                        $item['weight_declared'] = $weightDeclared;

                        if ($holderId === 1) {
                            $item['custom_l_cm'] = self::CUSTOM_DIMS[array_rand(self::CUSTOM_DIMS)];
                            $item['custom_w_cm'] = self::CUSTOM_DIMS[array_rand(self::CUSTOM_DIMS)];
                            $item['custom_h_cm'] = self::CUSTOM_DIMS[array_rand(self::CUSTOM_DIMS)];
                        }
                    }

                    $weightGross = round($item['weight_declared'] * 1.05, 1);
                    $item['weight_gross'] = $weightGross;
                    $item['weight_tare']  = round($weightGross - $item['weight_declared'], 1);

                    DB::table('order_items')->insert($item);
                }
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();

        $orders = DB::table('orders')->count();
        $items  = DB::table('order_items')->count();
        $this->command->info("Seeding completato — ordini: {$orders}, item: {$items}");
    }

    private function rollBulk(string $category): bool
    {
        $prob = self::BULK_PROBABILITY[$category] ?? 0.0;
        return (random_int(0, 100) / 100) < $prob;
    }

    private function weightedRandom(array $weights): string
    {
        $total      = array_sum($weights);
        $rand       = random_int(1, $total);
        $cumulative = 0;
        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }
        return array_key_last($weights);
    }
}
