<?php

namespace App\Console\Commands;

use App\Services\HealthMonitorService;
use Illuminate\Console\Command;

class ProcessInactiveDevicesCommand extends Command
{
    protected $signature = 'health:scan-inactive';

    protected $description = 'Emit health events for devices that have gone inactive.';

    public function handle(HealthMonitorService $healthMonitor): int
    {
        $processed = $healthMonitor->processInactiveDevices();
        $this->info("Processed {$processed} inactive devices.");

        return self::SUCCESS;
    }
}
