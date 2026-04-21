<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $upsert = function (string $name, ?int $parentId, float $baseCo2Kg, float $reusePct, float $ecoPoints): Category {
            return Category::query()->updateOrCreate(
                ['name' => $name, 'parent_id' => $parentId],
                [
                    'base_co2_kg' => $baseCo2Kg,
                    'reuse_pct' => $reusePct,
                    'eco_points' => $ecoPoints,
                ]
            );
        };

        $clothing = $upsert('Clothing', null, 0, 0, 0);
        $electronics = $upsert('Electronics', null, 0, 0, 0);
        $furniture = $upsert('Furniture', null, 0, 0, 0);
        $books = $upsert('Books', null, 0, 0, 0);
        $toys = $upsert('Toys & Games', null, 0, 0, 0);
        $appliances = $upsert('Home Appliances', null, 0, 0, 0);
        $sports = $upsert('Sports Equipment', null, 0, 0, 0);

        $upsert('Tops & T-Shirts', $clothing->id, 7.00, 75, 5.25);
        $upsert('Jeans & Trousers', $clothing->id, 30.00, 75, 22.50);
        $upsert('Jackets & Coats', $clothing->id, 25.00, 75, 18.75);
        $upsert('Dresses & Skirts', $clothing->id, 15.00, 75, 11.25);
        $upsert('Shoes', $clothing->id, 14.00, 75, 10.50);

        $upsert('Smartphones', $electronics->id, 60.00, 70, 42.00);
        $upsert('Tablets', $electronics->id, 110.00, 70, 77.00);
        $upsert('Laptops', $electronics->id, 270.00, 80, 216.00);
        $upsert('Desktop PCs', $electronics->id, 300.00, 80, 240.00);
        $upsert('Cameras', $electronics->id, 80.00, 75, 60.00);

        $upsert('Chairs', $furniture->id, 16.00, 82, 13.10);
        $upsert('Tables & Desks', $furniture->id, 70.00, 82, 57.40);
        $upsert('Sofas & Couches', $furniture->id, 105.00, 82, 86.10);
        $upsert('Storage & Shelving', $furniture->id, 50.00, 82, 41.00);

        $upsert('Books', $books->id, 5.00, 87, 4.35);

        $upsert('Plastic Toys', $toys->id, 6.00, 75, 4.50);
        $upsert('Wooden Toys', $toys->id, 3.00, 80, 2.40);
        $upsert('Board Games', $toys->id, 3.00, 80, 2.40);

        $upsert('Washing Machines', $appliances->id, 350.00, 25, 87.50);
        $upsert('Refrigerators', $appliances->id, 350.00, 25, 87.50);
        $upsert('Microwaves', $appliances->id, 80.00, 25, 20.00);

        $upsert('Bicycles', $sports->id, 95.00, 80, 76.00);
        $upsert('Gym Equipment', $sports->id, 10.00, 75, 7.50);
        $upsert('Outdoor Gear', $sports->id, 8.00, 75, 6.00);
    }
}
