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
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Medical Data Inputs
            $table->tinyInteger('gender'); // 0: Female, 1: Male
            $table->float('age');
            $table->tinyInteger('hypertension'); // 0 or 1
            $table->tinyInteger('heart_disease'); // 0 or 1
            $table->tinyInteger('smoking_history'); // 0-5
            $table->float('bmi');
            $table->float('hba1c_level');
            $table->float('blood_glucose_level');
            
            // Results
            $table->tinyInteger('prediction'); // 0: Normal, 1: Diabetes Risk
            $table->float('probability'); // Percentage/prob value
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};
