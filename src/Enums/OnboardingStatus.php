<?php

namespace TrackAnyDevice\Core\Enums;

enum OnboardingStatus: string
{
    case Pending = 'pending';
    case SimAdded = 'sim_added';
    case Configured = 'configured';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::SimAdded => 'SIM Added',
            self::Configured => 'Configured',
            self::Verified => 'Verified',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::SimAdded => 'warning',
            self::Configured => 'info',
            self::Verified => 'success',
        };
    }
}
