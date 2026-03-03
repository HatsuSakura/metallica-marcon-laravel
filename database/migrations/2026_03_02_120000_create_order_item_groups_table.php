<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_item_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cer_code_id')->constrained('cer_codes')->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();

            $table->index(['order_id', 'cer_code_id']);
            $table->unique(['order_id', 'cer_code_id', 'label']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('order_item_group_id')
                ->nullable()
                ->after('cer_code_id')
                ->constrained('order_item_groups')
                ->nullOnDelete();
        });

        DB::transaction(function () {
            $pairs = DB::table('order_items')
                ->select('order_id', 'cer_code_id')
                ->whereNull('deleted_at')
                ->whereNotNull('order_id')
                ->whereNotNull('cer_code_id')
                ->distinct()
                ->get();

            foreach ($pairs as $pair) {
                $cerCode = DB::table('cer_codes')
                    ->where('id', $pair->cer_code_id)
                    ->value('code') ?? (string) $pair->cer_code_id;

                $groupId = DB::table('order_item_groups')->insertGetId([
                    'order_id' => $pair->order_id,
                    'cer_code_id' => $pair->cer_code_id,
                    'label' => "{$cerCode}.1",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('order_items')
                    ->where('order_id', $pair->order_id)
                    ->where('cer_code_id', $pair->cer_code_id)
                    ->whereNull('order_item_group_id')
                    ->update(['order_item_group_id' => $groupId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_item_group_id');
        });

        Schema::dropIfExists('order_item_groups');
    }
};
