<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Region;
use Illuminate\Database\Seeder;

class HomepageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $category = fn (string $slug) => Category::query()->where('slug', $slug)->value('id');
        $region = Region::query()->where('slug', 'regiao-de-limeira')->value('id');

        $sections = [
            ['hero', 'Destaques', HomepageSection::TYPE_HERO, null, null, 5],
            ['ultimas', 'Últimas notícias', HomepageSection::TYPE_LATEST, null, null, 8],
            ['limeira', 'Limeira', HomepageSection::TYPE_CATEGORY, $category('limeira'), null, 6],
            ['regiao', 'Região', HomepageSection::TYPE_REGION, null, $region, 6],
            ['politica', 'Política', HomepageSection::TYPE_CATEGORY, $category('politica'), null, 4],
            ['economia', 'Economia', HomepageSection::TYPE_CATEGORY, $category('economia'), null, 4],
            ['esportes', 'Esportes', HomepageSection::TYPE_CATEGORY, $category('esportes'), null, 4],
            ['mais-lidas', 'Mais lidas', HomepageSection::TYPE_MOST_READ, null, null, 5],
        ];

        foreach ($sections as $i => [$key, $title, $type, $categoryId, $regionId, $limit]) {
            HomepageSection::query()->updateOrCreate(['key' => $key], [
                'title' => $title,
                'section_type' => $type,
                'category_id' => $categoryId,
                'region_id' => $regionId,
                'limit' => $limit,
                'sort_order' => $i + 1,
                'is_visible' => true,
            ]);
        }
    }
}
