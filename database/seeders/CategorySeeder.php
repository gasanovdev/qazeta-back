<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Biznes',
            'Maliyyə',
            'Gündəm',
            'İdman',
            'Tarixi',
            'Siyasi',
            'Süni İntellekt',
            'Texnologiya',
            'Müharibələr & Konfliktlər',
            'Moda',
            'Kripto',
            'Elmi',
            'Əlavələr',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(['name' => $name]);
        }
    }
}
