<?php

namespace TrackAnyDevice\Core\Jobs\Workflows;

use TrackAnyDevice\Core\Models\Workflow;
use TrackAnyDevice\Core\Workflows\WorkflowExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $workflowId,
        public string $triggeredBy,
        /** @var array<string, mixed> */
        public array $context,
    ) {}

    public function handle(WorkflowExecutor $executor): void
    {
        $workflow = Workflow::find($this->workflowId);
        if (! $workflow || ! $workflow->is_enabled) {
            return;
        }

        $executor->run($workflow, $this->triggeredBy, $this->context);
    }
}
