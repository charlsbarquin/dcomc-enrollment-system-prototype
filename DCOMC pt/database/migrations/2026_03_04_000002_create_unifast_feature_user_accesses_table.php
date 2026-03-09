<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unifast_feature_user_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('feature');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unifast_feature_user_accesses');
    }
};
