<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // User / client information
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();

            // Session information
            $table->string('language', 10);
            $table->date('session_date');
            $table->time('session_time');
            $table->text('note')->nullable();

            // Booking lifecycle
            $table->string('status')->default('pending');
            // pending, confirmed, completed, cancelled

            // Zoom
            $table->string('zoom_link')->nullable();

            // Subscription/session tracking
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('session_credit_used')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
