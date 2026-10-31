<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /** @var array<string, array{0: string, 1: string}> name => [display_name, group] */
    private const PERMISSIONS = [
        'articles.view' => ['Ver matérias', 'Matérias'],
        'articles.create' => ['Criar matérias', 'Matérias'],
        'articles.update' => ['Editar matérias', 'Matérias'],
        'articles.delete' => ['Excluir matérias', 'Matérias'],
        'articles.submit' => ['Enviar para revisão', 'Matérias'],
        'articles.approve' => ['Aprovar matérias', 'Matérias'],
        'articles.publish' => ['Publicar matérias', 'Matérias'],
        'articles.archive' => ['Arquivar matérias', 'Matérias'],
        'categories.manage' => ['Gerenciar categorias', 'Taxonomia'],
        'media.upload' => ['Enviar mídia', 'Mídia'],
        'homepage.manage' => ['Gerenciar home', 'Site'],
        'pages.manage' => ['Gerenciar páginas', 'Site'],
        'menus.manage' => ['Gerenciar menus', 'Site'],
        'users.manage' => ['Gerenciar usuários', 'Administração'],
        'roles.manage' => ['Gerenciar papéis', 'Administração'],
        'ads.manage' => ['Gerenciar publicidade', 'Comercial'],
        'newsletter.manage' => ['Gerenciar newsletter', 'Comercial'],
        'analytics.view' => ['Ver relatórios', 'Relatórios'],
        'audit.view' => ['Ver auditoria', 'Administração'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => [$display, $group]) {
            Permission::query()->updateOrCreate(['name' => $name], ['display_name' => $display, 'group' => $group]);
        }

        $all = array_keys(self::PERMISSIONS);

        $matrix = [
            RoleName::Superadmin->value => ['Acesso total ao sistema.', $all],
            RoleName::Admin->value => ['Administra o portal, usuários e configurações.', array_values(array_diff($all, ['roles.manage']))],
            RoleName::EditorChefe->value => ['Comanda a redação e a linha editorial.', [
                'articles.view', 'articles.create', 'articles.update', 'articles.delete', 'articles.approve',
                'articles.publish', 'articles.archive', 'categories.manage', 'media.upload', 'homepage.manage',
                'pages.manage', 'menus.manage', 'analytics.view',
            ]],
            RoleName::Editor->value => ['Edita, aprova e publica matérias.', [
                'articles.view', 'articles.create', 'articles.update', 'articles.approve',
                'articles.publish', 'articles.archive', 'media.upload', 'analytics.view',
            ]],
            RoleName::Journalist->value => ['Escreve matérias e envia para revisão.', [
                'articles.view', 'articles.create', 'articles.update', 'articles.submit', 'media.upload',
            ]],
            RoleName::Columnist->value => ['Escreve colunas e artigos de opinião.', [
                'articles.view', 'articles.create', 'articles.update', 'articles.submit', 'media.upload',
            ]],
            RoleName::Commercial->value => ['Gerencia anunciantes e campanhas.', ['ads.manage', 'newsletter.manage', 'analytics.view']],
            RoleName::Analyst->value => ['Acompanha métricas e relatórios.', ['analytics.view']],
        ];

        foreach (RoleName::cases() as $case) {
            [$description, $permissions] = $matrix[$case->value];

            $role = Role::query()->updateOrCreate(
                ['name' => $case->value],
                ['display_name' => $case->label(), 'description' => $description],
            );

            $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id'));
        }

        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'email' => 'admin@limeiranews.com.br',
                'name' => 'Administrador Limeira News',
                'public_name' => 'Redação Limeira News',
                'slug' => 'redacao-limeira-news',
                'password' => 'ChangeMe!Admin1',
                'role' => RoleName::Superadmin,
                'specialty' => 'Administração',
                'bio' => 'Conta administrativa do portal Limeira News.',
            ],
            [
                'email' => 'jornalista@limeiranews.com.br',
                'name' => 'Marina Souza',
                'public_name' => 'Marina Souza',
                'slug' => 'marina-souza',
                'password' => 'ChangeMe!Jornal1',
                'role' => RoleName::Journalist,
                'specialty' => 'Cidade e política local',
                'bio' => 'Repórter de cidade, cobre política, serviços públicos e economia local em Limeira e região.',
            ],
            [
                'email' => 'editor@limeiranews.com.br',
                'name' => 'Carlos Menezes',
                'public_name' => 'Carlos Menezes',
                'slug' => 'carlos-menezes',
                'password' => 'ChangeMe!Editor1',
                'role' => RoleName::Editor,
                'specialty' => 'Edição e política',
                'bio' => 'Editor responsável pela revisão e publicação das matérias do Limeira News.',
            ],
        ];

        foreach ($users as $u) {
            User::query()->updateOrCreate(['email' => $u['email']], [
                'name' => $u['name'],
                'public_name' => $u['public_name'],
                'slug' => $u['slug'],
                'password' => Hash::make($u['password']),
                'role_id' => Role::query()->where('name', $u['role']->value)->value('id'),
                'specialty' => $u['specialty'],
                'bio' => $u['bio'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
