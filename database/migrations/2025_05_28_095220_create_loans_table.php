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
        Schema::create('loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            // $table->foreignUlid('book_id')->constrained('books')->cascadeOnDelete();
            $table->date('tanggal_kembali');
            $table->unsignedInteger('denda')->default(0);
            $table->string('status_peminjaman')->default('Dipinjam');
            $table->string('status_denda')->default('Lunas');
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
