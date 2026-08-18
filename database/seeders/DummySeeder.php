<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Empresa
        $company = Company::create([
            'name' => 'Agência de Marketing Strasa',
            'slug' => 'agencia-strasa',
        ]);

        // 2. Criar Usuários
        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Administrador',
            'email' => 'admin@strasa.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $colab = User::create([
            'company_id' => $company->id,
            'name' => 'João Designer',
            'email' => 'joao@strasa.com',
            'password' => Hash::make('password'),
            'role' => 'colaborador',
        ]);

        // 3. Criar Cliente
        $client = Client::create([
            'company_id' => $company->id,
            'name' => 'Tech Solutions Brasil',
            'slug' => 'tech-solutions',
            'segment' => 'Tecnologia',
            'social_networks' => ['instagram', 'linkedin', 'blog'],
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);

        $client2 = Client::create([
            'company_id' => $company->id,
            'name' => 'Clínica Odonto Vida',
            'slug' => 'odonto-vida',
            'segment' => 'Saúde',
            'social_networks' => ['instagram', 'facebook'],
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);

        // 4. Criar Projetos
        $project1 = Project::create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'name' => 'Redes Sociais 2026',
            'description' => 'Planejamento de redes sociais para 2026',
        ]);

        $project2 = Project::create([
            'client_id' => $client2->id,
            'company_id' => $company->id,
            'name' => 'Campanha Sorriso Perfeito',
            'description' => 'Campanha de captação',
        ]);

        // 5. Configurar Colunas
        foreach (Client::DEFAULT_COLUMNS as $col) {
            $col['company_id'] = $company->id;
            $project1->columns()->create($col);
            $project2->columns()->create($col);
        }

        // 6. Criar Tarefas
        $colAFazer = $project1->columns()->where('name', 'A Fazer')->first();
        $colEmAndamento = $project1->columns()->where('name', 'Em Andamento')->first();
        $colPostado = $project1->columns()->where('name', 'Postado')->first();

        Task::create([
            'client_id' => $client->id,
            'project_id' => $project1->id,
            'company_id' => $company->id,
            'column_id' => $colAFazer->id,
            'title' => 'Post: Nova IA no mercado',
            'description' => 'Criar carrossel com 5 dicas sobre como usar IA',
            'content_type' => 'carrossel',
            'social_networks' => ['instagram', 'linkedin'],
            'publish_date' => now()->addDays(2),
            'assignee_id' => $colab->id,
        ]);

        Task::create([
            'client_id' => $client->id,
            'project_id' => $project1->id,
            'company_id' => $company->id,
            'column_id' => $colEmAndamento->id,
            'title' => 'Blog: O que esperar de 2026',
            'description' => 'Texto de 1000 palavras para o blog',
            'content_type' => 'blog',
            'social_networks' => ['blog'],
            'publish_date' => now()->addDays(5),
            'assignee_id' => $admin->id,
        ]);

        Task::create([
            'client_id' => $client->id,
            'project_id' => $project1->id,
            'company_id' => $company->id,
            'column_id' => $colPostado->id,
            'title' => 'Reels: Lançamento do Produto',
            'description' => 'Vídeo de 30s',
            'content_type' => 'reel',
            'social_networks' => ['instagram'],
            'publish_date' => now()->subDays(1),
            'assignee_id' => $colab->id,
            'is_published' => true,
        ]);
    }
}
