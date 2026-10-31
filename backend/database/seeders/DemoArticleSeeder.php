<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\City;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO CONTENT ONLY — for local development.
 * Fictional articles written in the Limeira News voice; do not run in production.
 */
class DemoArticleSeeder extends Seeder
{
    private const DEMO_NOTE = '<p><em>Conteúdo de demonstração criado para testes do portal Limeira News. Fatos, nomes e números são fictícios.</em></p>';

    private const IMG = 'https://images.unsplash.com/';

    public function run(): void
    {
        $author = User::query()->where('email', 'jornalista@limeiranews.com.br')->firstOrFail();
        $editor = User::query()->where('email', 'editor@limeiranews.com.br')->first();
        $region = Region::query()->where('slug', 'regiao-de-limeira')->first();
        $slugs = app(SlugService::class);

        $categories = Category::query()->pluck('id', 'slug');
        $cities = City::query()->pluck('id', 'slug');

        foreach ($this->articles() as $i => $a) {
            if (Article::query()->withTrashed()->where('title', $a['title'])->exists()) {
                continue;
            }

            $article = new Article([
                'title' => $a['title'],
                'subtitle' => $a['subtitle'],
                'summary' => $a['summary'],
                'body' => '<p>'.implode('</p><p>', $a['body']).'</p>'.self::DEMO_NOTE,
                'cover_url' => self::IMG.$a['img'].'?auto=format&fit=crop&w=1200&q=80',
                'primary_category_id' => $categories[$a['category']],
                'city_id' => $a['city'] ? ($cities[$a['city']] ?? null) : null,
                'region_id' => $region?->id,
                'is_featured' => $a['featured'] ?? false,
                'is_breaking' => false,
                'source_name' => 'Redação Limeira News (demonstração)',
                'image_credit' => 'Unsplash',
                'meta_title' => $a['title'].' | Limeira News',
                'meta_description' => $a['summary'],
                'allow_index' => false, // demo content should not be indexed
            ]);
            $article->slug = $slugs->unique($a['title'], Article::class);
            $article->author_id = $author->id;
            $article->editor_id = $editor?->id;
            $article->status = ArticleStatus::Published;
            $article->published_at = now()->subHours(($i * 7) + 2);
            $article->view_count = random_int(40, 1800);
            $article->save();

            $article->tags()->sync(
                collect([...$a['tags'], 'demonstração'])->map(
                    fn (string $name) => Tag::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id
                )->all()
            );

            if (! empty($a['secondary'])) {
                $article->categories()->sync(collect($a['secondary'])->map(fn ($s) => $categories[$s])->all());
            }
        }

        $first = Article::query()->where('is_featured', true)->latest('published_at')->first();
        if ($first && ! BreakingNews::query()->exists()) {
            BreakingNews::query()->create([
                'article_id' => $first->id,
                'headline' => 'DEMO: '.$first->title,
                'is_active' => true,
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addDays(30),
                'position' => 'top',
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articles(): array
    {
        return [
            [
                'title' => 'Prefeitura de Limeira anuncia pacote de obras de drenagem para bairros alagáveis',
                'subtitle' => 'Intervenções devem começar no próximo mês e priorizam regiões afetadas pelas chuvas de verão',
                'summary' => 'Obras de drenagem e limpeza de galerias atendem bairros que sofrem com alagamentos recorrentes.',
                'category' => 'limeira', 'city' => 'limeira', 'featured' => true, 'img' => 'photo-1486406146926-c627a92ad1ab',
                'tags' => ['obras', 'prefeitura'], 'secondary' => ['politica'],
                'body' => [
                    'A Prefeitura de Limeira apresentou nesta semana um pacote de obras de drenagem voltado a bairros que enfrentam alagamentos a cada temporada de chuvas. As intervenções incluem limpeza de galerias, troca de bocas de lobo e ampliação de trechos de tubulação.',
                    'Segundo o cronograma divulgado, os trabalhos começam pelas áreas consideradas mais críticas e devem avançar por etapas ao longo do ano. Moradores ouvidos pela reportagem esperam que as obras reduzam os transtornos nos dias de temporal.',
                    'A administração informou que acompanhará o andamento com relatórios periódicos e que os canais de atendimento seguem abertos para novas solicitações.',
                ],
            ],
            [
                'title' => 'Câmara de Limeira discute novo calendário de sessões e participação popular',
                'subtitle' => 'Vereadores analisam proposta que amplia espaço para falas de moradores nas sessões ordinárias',
                'summary' => 'Proposta em debate prevê tribuna popular e transmissão ampliada das sessões da Câmara.',
                'category' => 'politica', 'city' => 'limeira', 'featured' => true, 'img' => 'photo-1541872703-74c5e44368f9',
                'tags' => ['câmara', 'vereadores'],
                'body' => [
                    'Vereadores de Limeira debateram nesta semana um projeto que reorganiza o calendário de sessões e amplia a participação da população nas discussões legislativas.',
                    'O texto prevê uma tribuna popular, com inscrição prévia, além de melhorias na transmissão online das sessões. Defensores da proposta dizem que a medida aproxima o Legislativo do cidadão.',
                    'A matéria segue para análise nas comissões e ainda não tem data para votação em plenário.',
                ],
            ],
            [
                'title' => 'Indústrias de Limeira projetam contratações para o segundo semestre',
                'subtitle' => 'Setores de joias folheadas, autopeças e alimentos lideram expectativas de vagas',
                'summary' => 'Levantamento local aponta otimismo moderado da indústria e abertura de vagas na cidade.',
                'category' => 'economia', 'city' => 'limeira', 'featured' => true, 'img' => 'photo-1460925895917-afdab827c52f',
                'tags' => ['emprego', 'indústria'],
                'body' => [
                    'Empresários de Limeira avaliam que o segundo semestre deve trazer novas contratações, principalmente em segmentos tradicionais da economia local, como joias folheadas, autopeças e alimentos.',
                    'A expectativa vem acompanhada de cautela: custos de produção e crédito ainda preocupam, mas o aumento de encomendas anima o setor.',
                    'Entidades locais afirmam que cursos de qualificação devem ser reforçados para atender à demanda por mão de obra.',
                ],
            ],
            [
                'title' => 'Cosmópolis e Limeira alinham ações para melhorar transporte intermunicipal',
                'subtitle' => 'Reunião entre prefeituras discute horários, integração e segurança nos pontos de ônibus',
                'summary' => 'Cidades vizinhas buscam soluções conjuntas para o deslocamento diário de trabalhadores.',
                'category' => 'regiao', 'city' => 'cosmopolis', 'featured' => false, 'img' => 'photo-1449824913935-59a10b8d2000',
                'tags' => ['transporte', 'região'], 'secondary' => ['transito-e-mobilidade'],
                'body' => [
                    'Representantes das prefeituras de Cosmópolis e Limeira se reuniram para discutir melhorias no transporte intermunicipal, usado diariamente por milhares de trabalhadores e estudantes.',
                    'Entre os pontos em análise estão a revisão de horários, a integração entre linhas e a melhoria da estrutura dos pontos de parada.',
                    'Um novo encontro deve acontecer nas próximas semanas para fechar um plano de trabalho conjunto.',
                ],
            ],
            [
                'title' => 'Americana recebe feira de negócios com foco em pequenas empresas da região',
                'subtitle' => 'Evento reúne palestras, rodadas de negócios e capacitações gratuitas',
                'summary' => 'Feira em Americana deve movimentar empreendedores de toda a região de Limeira.',
                'category' => 'regiao', 'city' => 'americana', 'featured' => false, 'img' => 'photo-1556761175-5973dc0f32e7',
                'tags' => ['empreendedorismo', 'eventos'], 'secondary' => ['economia'],
                'body' => [
                    'Americana sedia nesta semana uma feira de negócios voltada a micro e pequenas empresas, com participação de empreendedores de várias cidades da região.',
                    'A programação inclui palestras sobre gestão, crédito e vendas online, além de rodadas de negócios entre fornecedores e compradores.',
                    'Segundo os organizadores, a entrada é gratuita mediante inscrição prévia.',
                ],
            ],
            [
                'title' => 'Polícia reforça patrulhamento em áreas comerciais de Limeira no fim de ano',
                'subtitle' => 'Operação prevê mais viaturas e ações de orientação a lojistas',
                'summary' => 'Reforço no policiamento busca ampliar a sensação de segurança no comércio.',
                'category' => 'policia', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1453873623425-04e3d5c6f4c1',
                'tags' => ['segurança', 'comércio'],
                'body' => [
                    'O policiamento será reforçado nas áreas comerciais de Limeira durante o período de maior movimento do varejo. A operação inclui rondas a pé, viaturas em pontos estratégicos e orientação a lojistas.',
                    'Comerciantes avaliam que a presença policial ajuda a atrair clientes e inibe ocorrências.',
                    'A corporação orienta que a população denuncie situações suspeitas pelos canais oficiais.',
                ],
            ],
            [
                'title' => 'Time de Limeira estreia com vitória em campeonato amador da região',
                'subtitle' => 'Equipe venceu por 2 a 0 diante de torcida animada',
                'summary' => 'Estreia positiva anima torcedores e embala a equipe para a segunda rodada.',
                'category' => 'esportes', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1517649763962-0c623066013b',
                'tags' => ['futebol', 'campeonato'],
                'body' => [
                    'A equipe de Limeira estreou com o pé direito no campeonato amador da região ao vencer por 2 a 0 em jogo disputado em campo cheio de torcedores.',
                    'O treinador elogiou a postura do time e destacou a importância de manter a regularidade nas próximas rodadas.',
                    'A segunda rodada está marcada para o próximo fim de semana.',
                ],
            ],
            [
                'title' => 'Unidades de saúde ampliam horário de vacinação contra a gripe em Limeira',
                'subtitle' => 'Atendimento também será oferecido aos sábados em unidades selecionadas',
                'summary' => 'Secretaria de Saúde amplia horários para facilitar a imunização da população.',
                'category' => 'saude', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1576091160399-112ba8d25d1d',
                'tags' => ['vacinação', 'saúde pública'],
                'body' => [
                    'A Secretaria de Saúde de Limeira ampliou o horário de vacinação contra a gripe em unidades básicas, incluindo atendimento aos sábados em pontos selecionados.',
                    'A medida busca facilitar o acesso de trabalhadores e famílias, especialmente de grupos prioritários.',
                    'Para se vacinar, é necessário apresentar documento com foto e a caderneta de vacinação, quando houver.',
                ],
            ],
            [
                'title' => 'Escolas municipais recebem laboratórios de robótica e programação',
                'subtitle' => 'Projeto-piloto começa com cinco unidades e pretende alcançar toda a rede',
                'summary' => 'Alunos terão aulas práticas de lógica, programação e robótica educacional.',
                'category' => 'educacao', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1503676260728-1c00da094a0b',
                'tags' => ['escolas', 'tecnologia'], 'secondary' => ['tecnologia'],
                'body' => [
                    'Cinco escolas municipais de Limeira passam a contar com laboratórios de robótica e programação em um projeto-piloto que pretende se expandir para toda a rede.',
                    'Professores participaram de formação específica e as aulas terão foco em resolução de problemas e trabalho em equipe.',
                    'A Secretaria de Educação avaliará os resultados antes de ampliar a iniciativa.',
                ],
            ],
            [
                'title' => 'Agenda cultural: festival gratuito reúne música e gastronomia no centro de Limeira',
                'subtitle' => 'Programação inclui bandas locais, food trucks e atividades para crianças',
                'summary' => 'Festival ocupa praça central no fim de semana com entrada gratuita.',
                'category' => 'cultura-e-lazer', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1459749411175-04bf5292ceea',
                'tags' => ['festival', 'música'],
                'body' => [
                    'O centro de Limeira recebe, no fim de semana, um festival gratuito com música ao vivo, gastronomia e atividades para as crianças.',
                    'Bandas locais se revezam no palco, enquanto food trucks oferecem opções variadas para o público.',
                    'Os organizadores recomendam o uso de transporte coletivo, já que algumas ruas terão trânsito alterado.',
                ],
            ],
            [
                'title' => 'Startups de Limeira participam de programa regional de aceleração',
                'subtitle' => 'Iniciativa conecta empreendedores a mentores e investidores do interior paulista',
                'summary' => 'Programa busca fortalecer o ecossistema de inovação na região.',
                'category' => 'tecnologia', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1518770660439-4636190af475',
                'tags' => ['startups', 'inovação'], 'secondary' => ['economia'],
                'body' => [
                    'Startups de Limeira integram um programa regional de aceleração que oferece mentorias, capacitações e conexão com investidores.',
                    'A proposta é fortalecer o ecossistema de inovação do interior paulista e criar oportunidades para jovens empreendedores.',
                    'As inscrições para a próxima turma devem abrir em breve.',
                ],
            ],
            [
                'title' => 'Obras na Via Anhanguera exigem atenção de motoristas que passam por Limeira',
                'subtitle' => 'Interdições parciais ocorrem em horários de menor movimento',
                'summary' => 'Concessionária orienta motoristas a se programarem para evitar atrasos.',
                'category' => 'transito-e-mobilidade', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1494783367193-149034c05e8f',
                'tags' => ['rodovias', 'trânsito'], 'secondary' => ['sao-paulo'],
                'body' => [
                    'Motoristas que utilizam a Via Anhanguera no trecho de Limeira devem redobrar a atenção por conta de obras de manutenção que causam interdições parciais.',
                    'Os serviços ocorrem prioritariamente em horários de menor fluxo, mas pequenos congestionamentos podem se formar.',
                    'A concessionária recomenda que os condutores consultem os canais oficiais antes de viajar.',
                ],
            ],
            [
                'title' => 'Estado de São Paulo registra alta na procura por cursos técnicos gratuitos',
                'subtitle' => 'Interior paulista concentra parte das matrículas, incluindo a região de Limeira',
                'summary' => 'Cursos técnicos ganham espaço entre jovens em busca de emprego rápido.',
                'category' => 'sao-paulo', 'city' => null, 'featured' => false, 'img' => 'photo-1523240795612-9a054b0db644',
                'tags' => ['educação', 'emprego'], 'secondary' => ['educacao'],
                'body' => [
                    'A procura por cursos técnicos gratuitos cresceu no estado de São Paulo, com forte demanda também no interior, incluindo a região de Limeira.',
                    'Especialistas apontam que a formação rápida e conectada ao mercado de trabalho atrai jovens e adultos em busca de recolocação.',
                    'As vagas variam conforme a unidade, e os interessados devem acompanhar os editais.',
                ],
            ],
            [
                'title' => 'Brasil: novas regras de consumo e o que muda para o comércio local',
                'subtitle' => 'Lojistas de cidades do interior se preparam para adaptações',
                'summary' => 'Mudanças nacionais exigem atenção de pequenos comerciantes, inclusive em Limeira.',
                'category' => 'brasil', 'city' => null, 'featured' => false, 'img' => 'photo-1521791136064-7986c2920216',
                'tags' => ['comércio', 'consumidor'], 'secondary' => ['economia'],
                'body' => [
                    'Novas regras de proteção ao consumidor em discussão no país podem trazer mudanças para o comércio, inclusive em cidades do interior como Limeira.',
                    'Entidades de classe recomendam que lojistas acompanhem os detalhes e busquem orientação para adaptar processos de atendimento e troca.',
                    'Para o consumidor, a expectativa é de mais transparência nas relações de compra.',
                ],
            ],
            [
                'title' => 'Internacional: clima extremo preocupa agricultores e reflete nos preços no Brasil',
                'subtitle' => 'Efeitos chegam às prateleiras e aos mercados do interior paulista',
                'summary' => 'Eventos climáticos pelo mundo pressionam a produção agrícola e os custos de alimentos.',
                'category' => 'internacional', 'city' => null, 'featured' => false, 'img' => 'photo-1500382017468-9049fed747ef',
                'tags' => ['clima', 'agricultura'], 'secondary' => ['economia'],
                'body' => [
                    'Eventos climáticos extremos em diferentes partes do mundo preocupam produtores e podem influenciar o preço de alimentos no Brasil.',
                    'Analistas lembram que a conexão entre mercados faz com que impactos externos cheguem aos supermercados do interior paulista.',
                    'Especialistas recomendam planejamento e atenção às oscilações de preços nos próximos meses.',
                ],
            ],
            [
                'title' => 'Opinião: o que Limeira precisa discutir para crescer com qualidade de vida',
                'subtitle' => 'Coluna reflete sobre planejamento urbano, mobilidade e participação cidadã',
                'summary' => 'Texto de opinião propõe debate sobre planejamento urbano e o futuro da cidade.',
                'category' => 'opiniao', 'city' => 'limeira', 'featured' => false, 'img' => 'photo-1477959858617-67f85cf4f1df',
                'tags' => ['opinião', 'planejamento urbano'],
                'body' => [
                    'Limeira cresce, e com o crescimento vêm perguntas essenciais: como garantir mobilidade eficiente, áreas verdes e serviços de qualidade para todos os bairros?',
                    'O debate público precisa ir além de obras isoladas e considerar planejamento de longo prazo, com participação ativa dos moradores.',
                    'Este é um texto de opinião e reflete a visão de seu autor, não necessariamente a do portal.',
                ],
            ],
        ];
    }
}
