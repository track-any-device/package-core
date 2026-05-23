<?php

namespace TrackAnyDevice\Core\Console\Commands;

use Illuminate\Console\Command;
use TrackAnyDevice\Core\Models\IncomingSms;
use TrackAnyDevice\SmsGateway\SmsGatewayService;

class PollSmsInbox extends Command
{
    protected $signature = 'sms:poll-inbox';

    protected $description = 'Poll the SMS gateway inbox and store unprocessed messages';

    public function handle(SmsGatewayService $gateway): int
    {
        $messages = $gateway->inbox();
        $stored = 0;

        foreach ($messages as $msg) {
            $sender = ltrim($msg['sender'] ?? '', '+');
            $raw = $msg['message'] ?? '';
            $receivedAt = $gateway->parseGatewayDate($msg['date'] ?? '');

            $alreadyExists = IncomingSms::where('sender_number', $sender)
                ->where('raw_message', $raw)
                ->where('received_at', $receivedAt)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            IncomingSms::create([
                'raw_message' => $raw,
                'sender_number' => $sender,
                'received_at' => $receivedAt,
                'source' => 'gateway_api',
            ]);

            $gateway->deleteMessage($msg['index']);
            $stored++;
        }

        if ($stored > 0) {
            $this->info("Stored {$stored} new SMS message(s).");
        }

        return self::SUCCESS;
    }
}
