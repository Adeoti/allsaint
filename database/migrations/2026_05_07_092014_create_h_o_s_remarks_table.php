<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hos_remarks', function (Blueprint $table) {
            $table->id();
            $table->text('remark')->nullable();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('result_root_id')->constrained()->onDelete('cascade');
            $table->foreignId('hos_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['student_id', 'result_root_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hos_remarks');
    }
};