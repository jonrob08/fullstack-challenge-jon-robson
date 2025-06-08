<?php

namespace App\Console\Commands;

use App\Jobs\FetchWeatherForAllUsers;
use Illuminate\Console\Command;

class FetchAllWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:fetch-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch weather data for all users with location information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching job to fetch weather for all users...');
        
        FetchWeatherForAllUsers::dispatch()
            ->onQueue('weather');
        
        $this->info('Job dispatched successfully. Check logs for progress.');
        
        return 0;
    }
}