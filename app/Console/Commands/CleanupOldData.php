<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:cleanup';

    protected $description = 'Clean up old database records (events, movements, sensor readings) to free up storage';

    public function handle()
    {
        $this->info('Starting database cleanup...');

        // 1. Delete movement states (live location points) older than 1 month
        // We do not need historical 1-minute tracking of location after a month.
        $deletedMovements = \App\Models\TripMovementState::where('created_at', '<', now()->subMonth())->delete();
        $this->info("Deleted {$deletedMovements} old trip movement states.");

        // 2. Delete non-critical events older than 3 months
        $deletedEvents = \App\Models\Event::where('created_at', '<', now()->subMonths(3))
            ->where('severity', '!=', 'critical') // keep critical logs longer
            ->delete();
        $this->info("Deleted {$deletedEvents} old events.");

        // 3. Delete sensor_readings_aggregated older than 1 year (to support the 'last_year' dashboard filter)
        $deletedSensors = \Illuminate\Support\Facades\DB::table('sensor_readings_aggregated')
            ->where('window_start', '<', now()->subYear())
            ->delete();
        $this->info("Deleted {$deletedSensors} old sensor readings.");

        $this->info('Database cleanup completed successfully.');
    }
}
