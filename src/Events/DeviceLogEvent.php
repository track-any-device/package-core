<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use TrackAnyDevice\Core\Enums\DeviceLogDirection;
use TrackAnyDevice\Core\Enums\DeviceLogSource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Runtime device-connection log entry — broadcast only, never persisted.
 *
 * Drives the admin and tenant log viewers without storing anything in
 * the database. Each emission fires over Soketi to:
 *
 *   - `private-admin.device-logs`            (admins see all traffic)
 *   - `private-tenant.{id}.device-logs`      (tenant operators see their own)
 *
 * The payload is intentionally generous — full message bodies, parsed
 * envelopes, command parameters — so an engineer integrating a new
 * device can debug at the wire level without enabling extra log files.
 */
class DeviceLogEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly string $ts;

    public function __construct(
        public readonly DeviceLogSource $source,
        public readonly DeviceLogDirection $direction,
        public readonly string $summary,
        /** @var array<string, mixed> */
        public readonly array $payload = [],
        public readonly ?int $deviceId = null,
        public readonly ?string $imei = null,
        public readonly ?int $tenantId = null,
        public readonly string $level = 'info',
    ) {
        $this->ts = now()->toIso8601ZuluString();
    }

    public function broadcastAs(): string
    {
        return 'device-log';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.device-logs')];

        if ($this->tenantId !== null) {
            $channels[] = new PrivateChannel('tenant.'.$this->tenantId.'.device-logs');
        }

        return $channels;
    }

    /**
     * Payload sent to subscribers. Phone-like keys are scrubbed before
     * broadcast — admins and tenants see device_id + imei but never
     * SIM / GSM numbers (per CLAUDE.md privacy rules).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ts' => $this->ts,
            'source' => $this->source->value,
            'direction' => $this->direction->value,
            'level' => $this->level,
            'summary' => $this->summary,
            'device_id' => $this->deviceId,
            'imei' => $this->imei,
            'tenant_id' => $this->tenantId,
            'payload' => $this->scrubPayload($this->payload),
        ];
    }

    /**
     * Strip phone-number / SIM / GSM / secret keys from the payload so
     * the broadcast respects the same privacy guarantees as the public
     * Tenant API. Admins willing to inspect the raw values can still
     * read the underlying SMS row via Filament.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function scrubPayload(array $payload): array
    {
        $banned = ['sim', 'gsm', 'phone', 'sender_number', 'password', 'token', 'secret', 'apn'];

        return collect($payload)
            ->mapWithKeys(function ($value, $key) use ($banned) {
                $lower = strtolower((string) $key);
                foreach ($banned as $b) {
                    if (str_contains($lower, $b)) {
                        return [$key => '[redacted]'];
                    }
                }

                if (is_array($value)) {
                    return [$key => $this->scrubPayload($value)];
                }

                return [$key => $value];
            })
            ->all();
    }
}
