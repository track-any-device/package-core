<?php

namespace TrackAnyDevice\Core\Notifications;

use TrackAnyDevice\Core\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Incident $incident) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'event_type' => $this->incident->event_type->value,
            'event_label' => $this->incident->event_type->label(),
            'priority' => $this->incident->priority->value,
            'priority_label' => $this->incident->priority->label(),
            'device_id' => $this->incident->device_id,
            'message' => sprintf(
                '%s incident (%s priority) triggered for device #%d',
                $this->incident->event_type->label(),
                $this->incident->priority->label(),
                $this->incident->device_id,
            ),
            'url' => '/incidents/'.$this->incident->id,
        ];
    }
}
