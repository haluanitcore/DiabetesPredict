<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the analysis form fails validation when weight and height are missing.
     */
    public function test_analysis_form_requires_weight_and_height(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/analysis', [
            'age' => 30,
            'hypertension' => 0,
            'bmi' => 22.5,
            'hba1c_level' => 5.5,
            'blood_glucose_level' => 100,
        ]);

        $response->assertSessionHasErrors(['weight', 'height']);
    }

    /**
     * Test that validation fails if weight or height are out of bounds.
     */
    public function test_analysis_form_validates_weight_and_height_ranges(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/analysis', [
            'age' => 30,
            'hypertension' => 0,
            'weight' => 5, // min is 10
            'height' => 40, // min is 50
            'bmi' => 22.5,
            'hba1c_level' => 5.5,
            'blood_glucose_level' => 100,
        ]);

        $response->assertSessionHasErrors(['weight', 'height']);
    }
}
