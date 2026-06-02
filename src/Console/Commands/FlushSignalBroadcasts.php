<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Console\Commands;

use Illuminate\Console\Command;
use TrackAnyDevice\Core\Services\SignalBroadcastBuffer;

class FlushSignalBroadcasts extends Command
{
    protected $signature = 'signals:flush
        {--daemon : Run forever, flushing every `interval` seconds}
        {--interval=2 : Seconds between flushes when running as daemon}';

    protected $description = 'Flush buffered signals into per-tenant LocationsBatchEvent broadcasts.';

    public function handle(SignalBroadcastBuffer $buffer): int
    {
        $interval = max(1, (int) $this->option('interval'));

        if (! $this->option('daemon')) {
            $count = $buffer->flush();
            $this->info("Flushed {$count} locations.");

            return self::SUCCESS;
        }

        while (true) {
            $count = $buffer->flush();
            if ($count > 0) {
                $this->line('['.now()->toIso8601ZuluString().'] flushed='.$count);
            }
            sleep($interval);
        }
    }
}
