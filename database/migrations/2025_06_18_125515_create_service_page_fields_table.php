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
        Schema::create('service_page_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_page_id')->constrained('service_pages')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('price', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_page_fields');
    }
};
