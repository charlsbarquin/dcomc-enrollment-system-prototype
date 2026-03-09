<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('gender')->nullable()->after('middle_name');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('civil_status')->nullable()->after('date_of_birth');
            $table->string('phone')->nullable()->after('civil_status');
            
            $table->string('college')->nullable()->after('semester');
            $table->string('course')->nullable()->after('college');
            $table->integer('units_enrolled')->nullable()->after('course');
            $table->string('student_status')->nullable()->after('units_enrolled');
            $table->boolean('is_freshman')->default(false)->after('student_status');
            $table->string('high_school')->nullable()->after('is_freshman');
            $table->date('hs_graduation_date')->nullable()->after('high_school');
            
            $table->string('father_name')->nullable()->after('hs_graduation_date');
            $table->string('father_occupation')->nullable()->after('father_name');
            $table->string('mother_name')->nullable()->after('father_occupation');
            $table->string('mother_occupation')->nullable()->after('mother_name');
            $table->decimal('annual_income', 12, 2)->nullable()->after('mother_occupation');
            $table->integer('num_siblings')->nullable()->after('annual_income');
            
            $table->string('house_number')->nullable()->after('num_siblings');
            $table->string('street')->nullable()->after('house_number');
            $table->string('barangay')->nullable()->after('street');
            $table->string('municipality')->nullable()->after('barangay');
            $table->string('province')->nullable()->after('municipality');
            $table->string('zip_code')->nullable()->after('province');
            
            $table->string('boarding_house_number')->nullable()->after('zip_code');
            $table->string('boarding_street')->nullable()->after('boarding_house_number');
            $table->string('boarding_barangay')->nullable()->after('boarding_street');
            $table->string('boarding_municipality')->nullable()->after('boarding_barangay');
            $table->string('boarding_province')->nullable()->after('boarding_municipality');
            $table->string('boarding_phone')->nullable()->after('boarding_province');
            
            $table->boolean('profile_completed')->default(false)->after('boarding_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_name', 'first_name', 'middle_name', 'gender', 'date_of_birth', 'civil_status', 'phone',
                'college', 'course', 'units_enrolled', 'student_status', 'is_freshman', 'high_school', 'hs_graduation_date',
                'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'annual_income', 'num_siblings',
                'house_number', 'street', 'barangay', 'municipality', 'province', 'zip_code',
                'boarding_house_number', 'boarding_street', 'boarding_barangay', 'boarding_municipality', 'boarding_province', 'boarding_phone',
                'profile_completed'
            ]);
        });
    }
};
