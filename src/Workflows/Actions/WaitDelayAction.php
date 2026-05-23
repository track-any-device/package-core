<?php

namespace TrackAnyDevice\Core\Workflows\Actions;

use TrackAnyDevice\Core\Workflows\Contracts\WorkflowAction;

/**
 * Pause workflow execution for 1 to 180 seconds.
 *
 * The executor honours the wait at dispatch time — when this action
 * returns with status=delayed, the next step is queued with the
 * configured delay rather than dispatched immediately.
 */
class WaitDelayAction implements WorkflowAction
{
    public function execute(array $config, array $context): array
    {
        $seconds = (int) ($config['seconds'] ?? 10);
        $seconds = max(1, min(180, $seconds));

        return [
            'status' => 'completed',
            'output' => ['delay_seconds' => $seconds, 'directive' => 'delay_next_step'],
        ];
    }
}
