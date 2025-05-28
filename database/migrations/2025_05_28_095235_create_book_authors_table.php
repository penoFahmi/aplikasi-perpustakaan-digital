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
    //     Schema::create('book_authors', function (Blueprint $table) {
    //         $table->ulid()->primary();
    //         $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
    //         $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
    //         $table->timestamps();
    //     });
    // }

    public function up(): void
    {
        Schema::create('book_authors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('book_id'); // Ubah ke 'ulid'
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->ulid('author_id'); // Ubah ke 'ulid'
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_authors');
    }
};
