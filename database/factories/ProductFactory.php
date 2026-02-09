<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product; 

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
   protected $model = Product::class;
   
    public function definition(): array
{
    return [
        'user_id' => 1,
        'title' => fake()->sentence(3),
        'description' => fake()->paragraph(),
        'price' => fake()->randomFloat(2, 10, 500),
        'type' => $this->faker->randomElements(['sell', 'rent', 'swap'], rand(1, 3)),
        'category' => fake()->randomElement(['tech', 'clothes', 'books']),
        'image' => 'default.jpg',
    ];
}

}
