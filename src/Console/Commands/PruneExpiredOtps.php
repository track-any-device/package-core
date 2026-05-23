<?php

namespace TrackAnyDevice\Core\Console\Commands;

use TrackAnyDevice\Core\Models\OtpCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('otp:prune')]
#[Description('Delete expired OTP codes from the database')]
class PruneExpiredOtps extends Command
{
    public function handle(): int
    {
        $deleted = OtpCode::expired()->delete();

        $this->info("Pruned {$deleted} expired OTP code(s).");

        return self::SUCCESS;
    }
}
