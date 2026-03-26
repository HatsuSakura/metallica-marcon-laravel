<?php

use App\Enums\DispatchStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'dispatch_status')) {
                $table->enum('dispatch_status', array_column(DispatchStatus::cases(), 'value'))
                    ->default(DispatchStatus::PENDING->value)
                    ->after('status');
            }
            if (!Schema::hasColumn('journeys', 'dispatch_started_at')) {
                $table->timestamp('dispatch_started_at')->nullable()->after('dispatch_status');
            }
            if (!Schema::hasColumn('journeys', 'dispatch_managed_at')) {
                $table->timestamp('dispatch_managed_at')->nullable()->after('dispatch_started_at');
            }
            if (!Schema::hasColumn('journeys', 'dispatch_updated_at')) {
                $table->timestamp('dispatch_updated_at')->nullable()->after('dispatch_managed_at');
            }
        });

        // Backfill lightweight snapshot from existing journey_events history.
        $journeyIds = DB::table('journey_events')
            ->distinct()
            ->pluck('journey_id');

        foreach ($journeyIds as $journeyId) {
            $events = DB::table('journey_events')
                ->where('journey_id', $journeyId)
                ->orderBy('id')
                ->get(['id', 'payload', 'created_at']);

            $status = DispatchStatus::PENDING->value;
            $startedAt = null;
            $managedAt = null;
            $updatedAt = null;

            foreach ($events as $event) {
                $payload = json_decode((string) ($event->payload ?? 'null'), true);
                $code = is_array($payload) ? ($payload['event'] ?? null) : null;
                if (!is_string($code)) {
                    continue;
                }

                if ($startedAt === null && in_array($code, ['dispatch_plan_updated', 'dispatch_hold', 'dispatch_resume', 'single_load_done', 'double_load_done', 'temporary_storage_done'], true)) {
                    $startedAt = $event->created_at;
                }

                if ($code === 'dispatch_hold') {
                    $status = DispatchStatus::ON_HOLD->value;
                    $updatedAt = $event->created_at;
                    continue;
                }

                if (in_array($code, ['dispatch_resume', 'dispatch_plan_updated'], true)) {
                    $status = DispatchStatus::IN_PROGRESS->value;
                    $updatedAt = $event->created_at;
                    continue;
                }

                if (in_array($code, ['single_load_done', 'double_load_done', 'temporary_storage_done'], true)) {
                    $status = DispatchStatus::MANAGED->value;
                    $managedAt = $event->created_at;
                    $updatedAt = $event->created_at;
                }
            }

            DB::table('journeys')
                ->where('id', $journeyId)
                ->update([
                    'dispatch_status' => $status,
                    'dispatch_started_at' => $startedAt,
                    'dispatch_managed_at' => $managedAt,
                    'dispatch_updated_at' => $updatedAt,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $toDrop = [];
            foreach (['dispatch_status', 'dispatch_started_at', 'dispatch_managed_at', 'dispatch_updated_at'] as $column) {
                if (Schema::hasColumn('journeys', $column)) {
                    $toDrop[] = $column;
                }
            }
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
