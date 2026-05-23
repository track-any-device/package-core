<?php

namespace TrackAnyDevice\Core\Console\Commands;

use TrackAnyDevice\Core\Enums\WorkflowTriggerType;
use TrackAnyDevice\Core\Jobs\Workflows\RunWorkflowJob;
use TrackAnyDevice\Core\Models\Workflow;
use Cron\CronExpression;
use Illuminate\Console\Command;

/**
 * Drives time-based workflow triggers.
 *
 * Schedule this command every minute (in routes/console.php or
 * app/Console/Kernel.php). It walks every enabled workflow with
 * trigger_type=time and dispatches a RunWorkflowJob for each whose
 * cron expression matches the current minute.
 *
 * trigger_config for a time-trigger:
 *   {
 *     "cron":     "*\/15 * * * *",   // standard 5-field cron
 *     "timezone": "Asia/Karachi"    // optional, defaults to app.timezone
 *   }
 */
class RunScheduledWorkflowsCommand extends Command
{
    protected $signature = 'workflows:run-scheduled';

    protected $description = 'Dispatch time-triggered workflows that are due this minute';

    public function handle(): int
    {
        $workflows = Workflow::query()
            ->where('trigger_type', WorkflowTriggerType::Time->value)
            ->where('is_enabled', true)
            ->get();

        $dispatched = 0;

        foreach ($workflows as $workflow) {
            $cron = $workflow->trigger_config['cron'] ?? null;
            if (! $cron || ! is_string($cron)) {
                continue;
            }

            try {
                $expression = new CronExpression($cron);
                $timezone = $workflow->trigger_config['timezone'] ?? config('app.timezone', 'UTC');

                if ($expression->isDue(now($timezone))) {
                    RunWorkflowJob::dispatch(
                        $workflow->id,
                        WorkflowTriggerType::Time->value,
                        ['trigger' => ['cron' => $cron, 'fired_at' => now()->toIso8601String()]],
                    );
                    $dispatched++;
                }
            } catch (\Throwable $e) {
                $this->warn("Workflow {$workflow->id} cron error: ".$e->getMessage());
            }
        }

        $this->info("Dispatched {$dispatched} scheduled workflow(s).");

        return self::SUCCESS;
    }
}
