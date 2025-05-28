<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('loans', function (Blueprint $table) {
    //         $table->ulid('id')->primary();
    //         $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    //         $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
    //         $table->timestamps();
    //     });
    // }

    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id'); // Ubah ke 'ulid' untuk konsistensi
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('book_id');
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
