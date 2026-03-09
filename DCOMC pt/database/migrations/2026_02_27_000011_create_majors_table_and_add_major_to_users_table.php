<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('majors')) {
            Schema::create('majors', function (Blueprint $table) {
                $table->id();
                $table->string('program');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['program', 'name']);
            });
        }

        if (! Schema::hasColumn('users', 'major')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('major')->nullable()->after('course');
            });
        }

        $catalog = [
            'Bachelor of Elementary Education' => [
                'General Education',
            ],
            'Bachelor of Secondary Education' => [
                'English',
                'Filipino',
                'Mathematics',
                'Science',
                'Social Studies',
                'Values Education',
            ],
            'Bachelor of Culture and Arts Education' => [
                'Culture and Arts',
            ],
            'Bachelor of Physical Education' => [
                'Physical Education',
            ],
            'Bachelor of Technical-Vocational Teacher Education' => [
                'Garments Fashion and Design',
                'Food Service Management',
            ],
            'Bachelor of Science in Entrepreneurship' => [
                'Entrepreneurship',
            ],
            'Teacher Certificate Program' => [
                'Teacher Certificate Program',
            ],
        ];

        foreach ($catalog as $program => $majors) {
            foreach ($majors as $major) {
                DB::table('majors')->updateOrInsert(
                    ['program' => $program, 'name' => $major],
                    ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'major')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('major');
            });
        }

        Schema::dropIfExists('majors');
    }
};

