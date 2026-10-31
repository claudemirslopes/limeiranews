<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionCitySeeder extends Seeder
{
    public function run(): void
    {
        $region = Region::query()->updateOrCreate(['slug' => 'regiao-de-limeira'], [
            'name' => 'Região de Limeira',
            'description' => 'Limeira e municípios vizinhos no interior de São Paulo.',
            'is_active' => true,
        ]);

        $cities = [
            ['Limeira', 'limeira'],
            ['Cosmópolis', 'cosmopolis'],
            ['Iracemápolis', 'iracemapolis'],
            ['Americana', 'americana'],
            ['Santa Bárbara d\'Oeste', 'santa-barbara-doeste'],
            ['Nova Odessa', 'nova-odessa'],
            ['Piracicaba', 'piracicaba'],
            ['Rio Claro', 'rio-claro'],
            ['Araras', 'araras'],
            ['Artur Nogueira', 'artur-nogueira'],
            ['Campinas', 'campinas'],
        ];

        foreach ($cities as [$name, $slug]) {
            City::query()->updateOrCreate(
                ['region_id' => $region->id, 'slug' => $slug],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
