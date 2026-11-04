<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MoveDriverJourneysToToday extends Command
{
    protected $signature = 'dev:journeys-today
        {--driver= : Email dell’autista (alternativa a --all-drivers)}
        {--all-drivers : Tutti gli autisti con viaggi assegnati}
        {--apply : Applica; senza questa opzione mostra solo l’anteprima}';

    protected $description = 'Solo locale: sposta a oggi i viaggi creati/attivi antecedenti, conservando ora italiana e durata';

    public function handle(): int
    {
        if (!app()->environment('local')) {
            $this->error('Comando consentito soltanto con APP_ENV=local.');
            return self::FAILURE;
        }

        $email = $this->option('driver');
        if ((bool) $email === (bool) $this->option('all-drivers')) {
            $this->error('Specificare --driver=email oppure --all-drivers.');
            return self::FAILURE;
        }

        $driverId = $email ? DB::table('users')->where('email', $email)->value('id') : null;
        if ($email && !$driverId) {
            $this->error('Autista non trovato.');
            return self::FAILURE;
        }

        // Il giorno di sviluppo è quello italiano, indipendentemente dal timezone UTC dell’app.
        $today = CarbonImmutable::now('Europe/Rome')->startOfDay();
        $this->info('Giorno di destinazione: '.$today->toDateString().' (Europe/Rome)');

        return DB::transaction(function () use ($driverId, $today) {
            $query = DB::table('journeys')
                ->whereNull('deleted_at')
                ->whereIn('status', ['creato', 'attivo'])
                ->whereNotNull('driver_id')
                ->where('planned_start_at', '<', $today->utc()->toDateTimeString())
                ->when($driverId, fn ($q) => $q->where('driver_id', $driverId))
                ->orderBy('id');

            if ($this->option('apply')) {
                $query->lockForUpdate();
            }

            $changes = [];
            foreach ($query->get(['id', 'driver_id', 'planned_start_at', 'planned_end_at']) as $journey) {
                $start = CarbonImmutable::parse($journey->planned_start_at, 'UTC');
                $newStart = $start->setTimezone('Europe/Rome')
                    ->setDate($today->year, $today->month, $today->day)->utc();
                $delta = $newStart->getTimestamp() - $start->getTimestamp();
                $changes[] = [
                    'id' => $journey->id,
                    'driver_id' => $journey->driver_id,
                    'before' => [
                        'planned_start_at' => $journey->planned_start_at,
                        'planned_end_at' => $journey->planned_end_at,
                    ],
                    'after' => [
                        'planned_start_at' => $newStart->toDateTimeString(),
                        'planned_end_at' => $journey->planned_end_at === null ? null
                            : CarbonImmutable::parse($journey->planned_end_at, 'UTC')->addSeconds($delta)->toDateTimeString(),
                    ],
                ];
            }

            $this->table(['Viaggio', 'Partenza precedente UTC', 'Nuova partenza UTC', 'Nuova fine UTC'],
                array_map(fn ($c) => [$c['id'], $c['before']['planned_start_at'],
                    $c['after']['planned_start_at'], $c['after']['planned_end_at']], $changes));

            if (!$this->option('apply') || !$changes) {
                $this->info(count($changes).' viaggi selezionati. Nessuna modifica; usare --apply per applicare.');
                return self::SUCCESS;
            }

            // Backup delle date prima delle scritture; nessun dato personale nel report.
            $path = 'dev-journey-date-shifts/'.Str::uuid().'.json';
            $disk = Storage::disk('local');
            if (!$disk->put($path, json_encode([
                'operation' => 'dev:journeys-today',
                'created_at' => CarbonImmutable::now('UTC')->toIso8601String(),
                'target_day' => $today->toDateString(),
                'timezone' => 'Europe/Rome',
                'changes' => $changes,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                throw new \RuntimeException('Impossibile salvare il report: nessuna modifica applicata.');
            }

            // Preparazione fixture locale: non simulare eventi operativi dell’autista.
            foreach ($changes as $change) {
                DB::table('journeys')->where('id', $change['id'])->update($change['after']);
            }

            $this->info(count($changes).' viaggi aggiornati. Report delle date: '.$disk->path($path));
            return self::SUCCESS;
        });
    }
}
