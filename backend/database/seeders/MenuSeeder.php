<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $header = Menu::query()->updateOrCreate(['location' => 'header'], ['name' => 'Menu principal']);
        $footer = Menu::query()->updateOrCreate(['location' => 'footer'], ['name' => 'Menu do rodapé']);

        $header->items()->delete();
        $footer->items()->delete();

        $slugs = ['limeira', 'regiao', 'politica', 'economia', 'policia', 'esportes', 'saude', 'educacao', 'cultura-e-lazer', 'opiniao'];
        $categories = Category::query()->whereIn('slug', $slugs)->get()->keyBy('slug');

        $order = 1;
        foreach ($slugs as $slug) {
            $category = $categories->get($slug);
            if (! $category) {
                continue;
            }

            MenuItem::query()->create([
                'menu_id' => $header->id,
                'label' => $category->name,
                'url' => '/categoria/'.$category->slug,
                'category_id' => $category->id,
                'sort_order' => $order++,
                'is_active' => true,
                'target' => '_self',
            ]);
        }

        $footerLinks = [
            ['Sobre o Limeira News', '/pagina/sobre'],
            ['Contato', '/pagina/contato'],
            ['Política editorial', '/pagina/politica-editorial'],
            ['Política de privacidade', '/pagina/politica-de-privacidade'],
            ['Termos de uso', '/pagina/termos-de-uso'],
            ['Anuncie', '/pagina/anuncie'],
        ];

        foreach ($footerLinks as $i => [$label, $url]) {
            MenuItem::query()->create([
                'menu_id' => $footer->id,
                'label' => $label,
                'url' => $url,
                'sort_order' => $i + 1,
                'is_active' => true,
                'target' => '_self',
            ]);
        }
    }
}
