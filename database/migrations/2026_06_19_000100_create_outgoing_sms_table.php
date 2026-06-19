<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outgoing SMS queue (Workstream: centralised SMS). `server-login` and `server-admin` enqueue an
 * outgoing-SMS request here (shared central DB via UsesCentralConnection); `app` is the sole sender
 * and delivers via the SMS gateway, updating status. See docs/audit/PENDING-CHANGES.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 30);
            $table->text('message');
            $table->string('source', 32)->default('app');     // login | admin | app | system
            $table->string('status', 16)->default('pending');  // pending | sending | sent | failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_sms');
    }
};
