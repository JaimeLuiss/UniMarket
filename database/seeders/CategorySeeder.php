<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentos y Snacks',
            'Tecnología y Artículos Personales',
            'Material Académico y Laboratorio',
            'Emprendimientos y Otros',
        ];

        foreach ($categories as $sortOrder => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $sortOrder],
            );
        }
    }
}
