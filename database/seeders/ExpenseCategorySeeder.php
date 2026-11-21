<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Utilities & Electricity',
            'Rent & Premises',
            'Logistics & Transport',
            'Maintenance & Repairs',
            'Salaries & Wages',
            'Office & Administrative',
            'Marketing & Advertising',
            'Packaging & Consumables',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(
                ['name' => $name, 'kind' => 'expense'],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true,
                    'color' => '#64748b',
                ]
            );
        }
    }
}

