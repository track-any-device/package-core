<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('device_id');
            $table->string('event_type', 60);
            $table->boolean('sms_enabled')->default(true);
            $table->timestamp('sms_disabled_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id', 'event_type'], 'udnp_user_device_event_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_notification_preferences');
    }
};
