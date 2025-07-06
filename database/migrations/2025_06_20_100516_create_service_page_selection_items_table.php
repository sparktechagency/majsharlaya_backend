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
        Schema::create('service_page_selection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_page_selection_id')->constrained('service_page_selections')->cascadeOnDelete();
            $table->string('selection_text');
            $table->string('type');
            $table->string('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_page_selection_items');
    }
};
