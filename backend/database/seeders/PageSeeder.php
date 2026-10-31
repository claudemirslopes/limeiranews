<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'sobre' => ['Sobre o Limeira News', '<p>O <strong>Limeira News</strong> é um portal de notícias dedicado a Limeira e região, com cobertura local, compromisso com a apuração e transparência com o leitor.</p><h2>Nossa missão</h2><p>Informar com clareza, rapidez e responsabilidade, dando voz às pessoas e aos assuntos que movem a cidade.</p>'],
            'contato' => ['Contato', '<p>Fale com a redação do Limeira News.</p><ul><li>Sugestões de pauta e denúncias: <a href="mailto:redacao@limeiranews.com.br">redacao@limeiranews.com.br</a></li><li>Comercial e publicidade: <a href="mailto:comercial@limeiranews.com.br">comercial@limeiranews.com.br</a></li></ul>'],
            'politica-editorial' => ['Política editorial', '<p>Todo conteúdo publicado passa por apuração e revisão editorial. Conteúdos patrocinados são sempre identificados.</p><h2>Correções</h2><p>Erros serão corrigidos de forma transparente. Envie sua solicitação para a redação.</p>'],
            'politica-de-privacidade' => ['Política de privacidade', '<p>Respeitamos a Lei Geral de Proteção de Dados (LGPD). Coletamos apenas os dados necessários para o funcionamento do portal e da newsletter, sempre com o seu consentimento.</p><h2>Newsletter</h2><p>Você pode cancelar a inscrição a qualquer momento.</p>'],
            'termos-de-uso' => ['Termos de uso', '<p>Ao utilizar o Limeira News você concorda com estes termos. O conteúdo é protegido por direitos autorais e não pode ser reproduzido sem autorização.</p>'],
            'anuncie' => ['Anuncie no Limeira News', '<p>Alcance leitores de Limeira e região com formatos publicitários identificados e transparentes.</p><p>Entre em contato com nosso time comercial.</p>'],
        ];

        foreach ($pages as $slug => [$title, $body]) {
            Page::query()->updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => $body,
                'meta_title' => $title.' | Limeira News',
                'meta_description' => mb_substr(strip_tags($body), 0, 200),
                'is_published' => true,
                'published_at' => now(),
            ]);
        }
    }
}
