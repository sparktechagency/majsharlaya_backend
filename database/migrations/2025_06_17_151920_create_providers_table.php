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
        Schema::create('providers', function (Blueprint $table) {
            $table->id(); // primary key
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // user delete হলে provider ও delete হবে
            $table->string('image')->nullable(); // profile image (optional)
            $table->string('name');              // provider নাম
            $table->json('type')->nullable();    // multiple category, যেমন: ["cutting", "plumbing"]
            $table->string('email')->unique();   // unique email
            $table->string('number')->nullable(); // ফোন নম্বর (optional)
            $table->string('address');            // ঠিকানা
            $table->unsignedInteger('completed_service')->default(0); // কতগুলো service complete করেছে
            $table->enum('status', ['Both', 'Available', 'Not available'])->default('Available'); // provider এর availability status
            $table->json('nid')->nullable();    // multiple nid image store (JSON)
            $table->timestamps(); // created_at, updated_at
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
