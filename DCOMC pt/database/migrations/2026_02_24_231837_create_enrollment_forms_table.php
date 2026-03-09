<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Untitled Form');
            $table->text('description')->nullable();
            
            // The JSON column is the magic part! It stores all the custom questions 
            // whether there is 1 question or 100 questions.
            $table->json('questions')->nullable(); 
            
            // This lets the registrar choose which form is currently "Active" for students
            $table->boolean('is_active')->default(false); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_forms');
    }
};