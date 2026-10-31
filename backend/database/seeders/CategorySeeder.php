<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $geographic = [
            ['Limeira', 'limeira', 'Notícias de Limeira: cidade, bairros, serviços, política e economia local.'],
            ['Região', 'regiao', 'O que acontece em Cosmópolis, Americana, Piracicaba, Rio Claro e cidades vizinhas.'],
            ['São Paulo', 'sao-paulo', 'Notícias do estado de São Paulo com impacto na nossa região.'],
            ['Brasil', 'brasil', 'Principais acontecimentos do país.'],
            ['Internacional', 'internacional', 'O mundo em perspectiva, com foco no que importa para o leitor local.'],
        ];

        foreach ($geographic as $i => [$name, $slug, $description]) {
            Category::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'type' => Category::TYPE_GEOGRAPHIC,
                'meta_title' => $name.' | Limeira News',
                'meta_description' => $description,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }

        $editorial = [
            ['Política', 'politica', 'Câmara, prefeitura, governo e eleições.'],
            ['Economia', 'economia', 'Indústria, comércio, emprego e o bolso do leitor.'],
            ['Polícia', 'policia', 'Segurança pública e ocorrências.'],
            ['Esportes', 'esportes', 'Futebol, esportes amadores e o time da cidade.'],
            ['Saúde', 'saude', 'Hospitais, vacinação e bem-estar.'],
            ['Educação', 'educacao', 'Escolas, universidades e oportunidades de ensino.'],
            ['Cultura e Lazer', 'cultura-e-lazer', 'Eventos, gastronomia, shows e agenda cultural.'],
            ['Tecnologia', 'tecnologia', 'Inovação, startups e tecnologia no dia a dia.'],
            ['Opinião', 'opiniao', 'Colunas e artigos assinados.'],
            ['Trânsito e Mobilidade', 'transito-e-mobilidade', 'Obras, transporte público e trânsito.'],
        ];

        foreach ($editorial as $i => [$name, $slug, $description]) {
            Category::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'type' => Category::TYPE_EDITORIAL,
                'meta_title' => $name.' | Limeira News',
                'meta_description' => $description,
                'is_active' => true,
                'sort_order' => 10 + $i,
            ]);
        }
    }
}
