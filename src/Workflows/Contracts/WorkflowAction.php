<?php

namespace TrackAnyDevice\Core\Workflows\Contracts;

/**
 * Contract for every workflow action handler.
 *
 * The executor walks the workflow graph from the trigger node and calls
 * execute() on each downstream action with:
 *   - $config: the node's data.config (validated/typed per action)
 *   - $context: the run input context (incident, device, tenant, time, …)
 *
 * Implementations return a result array that becomes the step's output
 * and is merged into the context for downstream steps.
 *
 * Implementations MUST NOT throw — surface errors via the returned array
 * so the step log can capture them and the executor can decide whether
 * to halt the run.
 */
interface WorkflowAction
{
    /**
     * @param  array<string, mixed>  $config  Node config from graph.data.config.
     * @param  array<string, mixed>  $context  Cumulative run context.
     * @return array{status: string, output?: array<string, mixed>, error?: ?string}
     */
    public function execute(array $config, array $context): array;
}
