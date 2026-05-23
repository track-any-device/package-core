<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Console\Commands;

use TrackAnyDevice\Core\Services\OfflineDeviceRecoveryService;
use Illuminate\Console\Command;

class DetectOfflineDevices extends Command
{
    protected $signature = 'devices:detect-offline';

    protected $description = 'Detect offline in-service devices and queue an onboarding or location-request SMS (skips TAD101; JT808 trackers use a longer silence threshold).';

    public function handle(OfflineDeviceRecoveryService $service): int
    {
        $stats = $service->detectAndDispatch();

        $this->info(sprintf(
            'Scanned %d device(s): %d onboarding, %d location-request, %d skipped, %d unreachable.',
            $stats['scanned'],
            $stats['onboarded'],
            $stats['requested'],
            $stats['skipped'],
            $stats['unreachable'],
        ));

        return self::SUCCESS;
    }
}
