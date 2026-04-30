<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('business_types')->insert([
            ['name' => 'Generico',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Industriale',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Commerciale',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Agricola',     'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('business_type_id')->nullable()->after('business_type');
            $table->foreign('business_type_id')->references('id')->on('business_types')->nullOnDelete();
        });

        $mapping = DB::table('business_types')->pluck('id', 'name')->mapWithKeys(
            fn ($id, $name) => [strtolower($name) => $id]
        )->toArray();

        foreach ($mapping as $enumValue => $newId) {
            DB::table('customers')
                ->where('business_type', $enumValue)
                ->update(['business_type_id' => $newId]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('business_type', ['generico', 'industriale', 'commerciale', 'agricola'])
                ->nullable()
                ->after('sdi_code');
        });

        $types = DB::table('business_types')->pluck('name', 'id');
        foreach ($types as $id => $name) {
            DB::table('customers')
                ->where('business_type_id', $id)
                ->update(['business_type' => strtolower($name)]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['business_type_id']);
            $table->dropColumn('business_type_id');
        });

        Schema::dropIfExists('business_types');
    }
};
