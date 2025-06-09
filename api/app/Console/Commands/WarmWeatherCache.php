<?php

namespace App\Console\Commands;

use App\Jobs\FetchWeatherForAllUsers;
use Illuminate\Console\Command;

class WarmWeatherCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:warm-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm the weather cache for all users to ensure fast response times';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weather cache warming...');
        
        // Dispatch the job
        FetchWeatherForAllUsers::dispatch();
        
        $this->info('Weather cache warming job dispatched. Check logs for progress.');
        
        // TODO: In production, this would be scheduled to run every 30 minutes
        // Add to app/Console/Kernel.php:
        // $schedule->command('weather:warm-cache')->everyThirtyMinutes();
        
        return 0;
    }
}