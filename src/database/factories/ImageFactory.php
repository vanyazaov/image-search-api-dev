<?php
// database/factories/ImageFactory.php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'original_filename' => $this->faker->word . '.jpg',
            'file_path' => 'images/' . $this->faker->word . '.jpg',
            'file_size' => $this->faker->numberBetween(1000, 1000000),
            'mime_type' => 'image/jpeg',
            'title' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['footwear', 'clothing', 'electronics']),
            'brand' => $this->faker->company,
            'is_active' => true,
        ];
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
