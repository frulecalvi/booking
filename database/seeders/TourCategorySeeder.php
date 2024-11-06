<?php

namespace Database\Seeders;

use App\Models\TourCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TourCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TourCategory::factory()->create(['name' => 'Category 1', 'slug' => 'category-1']);
        TourCategory::factory()->create(['name' => 'Category 2', 'slug' => 'category-2']);
    }
}
