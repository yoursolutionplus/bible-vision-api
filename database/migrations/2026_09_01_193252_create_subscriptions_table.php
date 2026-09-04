<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Customer
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Plan selected by the customer
            $table->foreignId('plan_id')
                ->constrained()
                ->restrictOnDelete();

            // Subscription state
            $table->string('status')->default('active');
            // active, cancelled, expired, paused

            // Support-session credits
            $table->unsignedInteger('sessions_remaining')->default(0);

            // Subscription dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Later: App Store / Google Play integration
            $table->string('payment_provider')->nullable();
            $table->string('provider_subscription_id')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
