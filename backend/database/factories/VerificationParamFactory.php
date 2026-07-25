<?php

namespace Database\Factories;

use App\Models\Verification;
use App\Models\VerificationParam;
use App\Models\VerificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationParam>
 */
class VerificationParamFactory extends Factory
{
    protected $model = VerificationParam::class;

    public function definition(): array
    {
        // Pick a random verification template (usually linked to the equipment's category)
        $template = VerificationTemplate::inRandomOrder()->first()
            ?? VerificationTemplate::factory()->create();

        return [
            'verification_id' => Verification::inRandomOrder()->first()?->id ?? Verification::factory(),
            'template_id' => $template->id,
            'value' => fake()->optional(0.9)->randomFloat(4, 0, 100),
            'result' => 'not_measured',
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
