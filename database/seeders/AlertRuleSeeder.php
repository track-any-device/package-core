<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Models\AlertRule;
use Illuminate\Database\Seeder;

/**
 * Seeds the default alert rules into the central database.
 *
 * Default rules are seeded with `tenant_id = null` so they apply globally
 * across every tenant. Tenant-specific overrides (added later through the
 * admin UI) carry a populated `tenant_id`.
 */
class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'SOS Alert',
                'description' => 'Triggered when a device SOS button is long-pressed.',
                'event_type' => AlertRuleEventType::Sos,
                'priority' => IncidentPriority::Critical,
                'notification_channels' => ['in_app'],
            ],
            [
                'name' => 'Beat Violation',
                'description' => 'Triggered when a device is detected outside its assigned beat.',
                'event_type' => AlertRuleEventType::BeatViolation,
                'priority' => IncidentPriority::High,
                'notification_channels' => ['in_app'],
            ],
            [
                'name' => 'Device Offline',
                'description' => 'Triggered when a device stops reporting for an extended period.',
                'event_type' => AlertRuleEventType::DeviceOffline,
                'priority' => IncidentPriority::High,
                'notification_channels' => ['in_app'],
            ],
        ];

        foreach ($rules as $rule) {
            AlertRule::updateOrCreate(
                [
                    'tenant_id' => null,
                    'event_type' => $rule['event_type']->value,
                    'scope' => 'all',
                ],
                array_merge($rule, ['scope' => 'all', 'is_enabled' => true]),
            );
        }
    }
}
