<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SessionLifetime extends Command
{
    protected $signature = 'session:lifetime {minutes=720} {--check : Verifica la configurazione effettiva senza modificarla}';
    protected $description = 'Configura la durata inattiva delle sessioni web senza esporre altri parametri ambiente';

    public function handle(): int
    {
        $minutes = filter_var($this->argument('minutes'), FILTER_VALIDATE_INT);
        if ($minutes === false || $minutes < 1 || $minutes > 1440) {
            $this->error('La durata deve essere compresa tra 1 e 1440 minuti.');
            return self::FAILURE;
        }
        if ($this->option('check')) {
            if ((int) config('session.lifetime') !== $minutes) {
                $this->error('La durata effettiva delle sessioni non corrisponde a quella richiesta.');
                return self::FAILURE;
            }
            $this->info("Durata sessioni verificata: {$minutes} minuti.");
            return self::SUCCESS;
        }
        $path = realpath($this->laravel->environmentFilePath());
        if (!$path || !is_writable($path)) {
            $this->error('Il file ambiente non è disponibile o non è scrivibile.');
            return self::FAILURE;
        }
        $mode = fileperms($path) & 0777;
        $contents = File::get($path);
        $replacement = "SESSION_LIFETIME={$minutes}";
        if (preg_match('/^SESSION_LIFETIME\h*=/m', $contents)) $contents = preg_replace('/^SESSION_LIFETIME\h*=.*$/m', $replacement, $contents);
        else $contents = rtrim($contents)."\n{$replacement}\n";
        File::replace($path, $contents, $mode);
        Artisan::call('config:clear');
        $this->info("Durata sessioni impostata: {$minutes} minuti. Ricostruire la cache di configurazione prima della verifica.");
        return self::SUCCESS;
    }
}
