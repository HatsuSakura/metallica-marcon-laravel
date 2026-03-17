<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'status')) {
            return;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $values = implode(
                ',',
                array_map(
                    fn (OrderStatus $status) => "'" . $status->value . "'",
                    OrderStatus::cases()
                )
            );

            DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM({$values}) NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'status')) {
            return;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            DB::table('orders')
                ->where('status', OrderStatus::STATUS_READY->value)
                ->update(['status' => OrderStatus::STATUS_CREATED->value]);

            $valuesWithoutReady = [
                OrderStatus::STATUS_CREATED->value,
                OrderStatus::STATUS_PLANNED->value,
                OrderStatus::STATUS_EXECUTED->value,
                OrderStatus::STATUS_DOWNLOADED->value,
                OrderStatus::STATUS_CLOSED->value,
            ];

            $values = implode(',', array_map(fn (string $value) => "'{$value}'", $valuesWithoutReady));
            DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM({$values}) NULL");
        }
    }
};

