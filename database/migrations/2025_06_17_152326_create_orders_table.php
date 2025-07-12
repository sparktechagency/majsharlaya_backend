<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('provider_company_id');
            $table->string('service_name');
            $table->unsignedBigInteger('assign_provider_id')->nullable();
            $table->json('details');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['Pending', 'Approve', 'Ongoing', 'Completed', 'Decline'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
