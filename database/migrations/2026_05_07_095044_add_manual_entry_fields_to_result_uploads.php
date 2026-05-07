<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_uploads', function (Blueprint $table) {

            if (!Schema::hasColumn('result_uploads', 'entry_type')) {
                $table->enum('entry_type', ['csv', 'manual'])
                    ->default('csv')
                    ->after('class_id');
            }

            if (!Schema::hasColumn('result_uploads', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('result_uploads', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            // Requires doctrine/dbal
            $table->json('file_path')->nullable()->change();

            // ❌ DO NOT add index
            // ❌ DO NOT drop index
            // Index already exists and is required by FK constraints
        });
    }

    public function down(): void
    {
        Schema::table('result_uploads', function (Blueprint $table) {

            if (Schema::hasColumn('result_uploads', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if (Schema::hasColumn('result_uploads', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('result_uploads', 'entry_type')) {
                $table->dropColumn('entry_type');
            }

            // ❌ DO NOT drop index
        });
    }
};
