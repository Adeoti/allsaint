<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadsheets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('term')->nullable();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->json('result_root_ids')->nullable(); // Array of selected result roots
            $table->json('generated_data')->nullable(); // Store the calculated broadsheet data
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadsheets');
    }
};