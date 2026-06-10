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
            $table->string('tenant_id');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('amount', 12, 2)->default(150000);
            $table->string('payment_method')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
