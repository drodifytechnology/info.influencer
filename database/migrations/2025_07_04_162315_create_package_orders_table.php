<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('package_orders', function (Blueprint $table) {
          $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('influencer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('awaiting'); // awaiting, active, complete, canceled
            $table->string('payment_status')->default('unpaid'); // paid, unpaid, rejected
            $table->double('amount')->nullable();
            $table->double('charge')->default(0);
            $table->double('discount_amount')->default(0);
            $table->double('total_amount'); // This is grand total amount including, charge, discount and amount
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_orders');
    }
};
