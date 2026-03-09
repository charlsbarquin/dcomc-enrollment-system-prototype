<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'block_id')) {
                $table->foreignId('block_id')->nullable()->after('semester')->constrained('blocks')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'shift')) {
                $table->enum('shift', ['day', 'night'])->nullable()->after('block_id');
            }
            if (! Schema::hasColumn('users', 'student_type')) {
                $table->string('student_type')->nullable()->after('student_status');
            }
            if (! Schema::hasColumn('users', 'status_color')) {
                $table->string('status_color')->nullable()->after('student_type');
            }
            if (! Schema::hasColumn('users', 'faculty_type')) {
                $table->string('faculty_type')->nullable()->after('status_color');
            }
            if (! Schema::hasColumn('users', 'max_units')) {
                $table->unsignedInteger('max_units')->nullable()->after('faculty_type');
            }
            if (! Schema::hasColumn('users', 'assigned_units')) {
                $table->unsignedInteger('assigned_units')->default(0)->after('max_units');
            }
            if (! Schema::hasColumn('users', 'accounting_access')) {
                $table->boolean('accounting_access')->default(false)->after('assigned_units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'block_id')) {
                $table->dropConstrainedForeignId('block_id');
            }

            $columns = [
                'shift',
                'student_type',
                'status_color',
                'faculty_type',
                'max_units',
                'assigned_units',
                'accounting_access',
            ];

            $droppable = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('users', $column)));

            if (! empty($droppable)) {
                $table->dropColumn($droppable);
            }
        });
    }
};

