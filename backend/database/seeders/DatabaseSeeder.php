<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategorySeeder::class,
            RegionCitySeeder::class,
            MenuSeeder::class,
            HomepageSectionSeeder::class,
            PageSeeder::class,
        ]);

        // Demo content is for local/testing only.
        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoArticleSeeder::class);
        }
    }
}
