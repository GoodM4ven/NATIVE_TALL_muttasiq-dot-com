<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Thikr;
use App\Services\Enums\ThikrTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Thikr>
 */
class ThikrFactory extends Factory
{
    /**
     * @var class-string<\App\Models\Thikr>
     */
    protected $model = Thikr::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'time' => $this->faker->randomElement(ThikrTime::cases()),
            'text' => $this->faker->sentence(8),
            'is_aayah' => $this->faker->boolean(20),
            'count' => $this->faker->numberBetween(1, 7),
        ];
    }
}
