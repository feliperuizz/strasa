<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Column;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $member;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Strasa Agência',
            'slug' => 'strasa-agencia',
        ]);

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Admin Strasa',
            'email' => 'admin@strasa.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->member = User::create([
            'company_id' => $this->company->id,
            'name' => 'João Colaborador',
            'email' => 'joao@strasa.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_MEMBER,
        ]);

        $this->client = Client::create([
            'company_id' => $this->company->id,
            'name' => 'Cliente Alpha',
            'slug' => 'cliente-alpha',
            'segment' => 'Tecnologia',
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);
    }

    public function test_collaborator_can_create_and_edit_project(): void
    {
        // 1. Criar projeto
        $response = $this->actingAs($this->member)->post(route('projects.store', $this->client), [
            'name' => 'Novo Projeto Colaborador',
            'description' => 'Descrição do projeto criado pelo colaborador',
        ]);

        $response->assertRedirect();

        $project = Project::where('name', 'Novo Projeto Colaborador')->first();
        $this->assertNotNull($project);
        $this->assertEquals($this->company->id, $project->company_id);
        $this->assertEquals($this->client->id, $project->client_id);

        // 2. Editar projeto
        $responseEdit = $this->actingAs($this->member)->patch(route('projects.update', $project), [
            'name' => 'Projeto Atualizado pelo Colaborador',
            'description' => 'Nova descrição',
        ]);

        $responseEdit->assertRedirect();
        $project->refresh();
        $this->assertEquals('Projeto Atualizado pelo Colaborador', $project->name);
    }

    public function test_collaborator_can_create_and_edit_tasks(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'Projeto Tarefas',
        ]);
        $project->createDefaultColumns();
        $column = $project->columns()->first();

        // 1. Criar tarefa
        $response = $this->actingAs($this->member)->post(route('tasks.store', $project), [
            'title' => 'Post de Lançamento',
            'column_id' => $column->id,
            'assignee_id' => $this->member->id,
            'content_type' => 'feed',
            'publish_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect();
        $task = Task::where('title', 'Post de Lançamento')->first();
        $this->assertNotNull($task);

        // 2. Editar tarefa
        $responseUpdate = $this->actingAs($this->member)->patch(route('tasks.update', $task), [
            'title' => 'Post de Lançamento (Revisado)',
            'column_id' => $column->id,
            'assignee_id' => $this->member->id,
        ]);

        $responseUpdate->assertRedirect();
        $task->refresh();
        $this->assertEquals('Post de Lançamento (Revisado)', $task->title);
    }

    public function test_collaborator_cannot_access_team_screen(): void
    {
        $response = $this->actingAs($this->member)->get(route('team.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_team_screen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('team.index'));
        $response->assertStatus(200);
        $response->assertSee('Equipe da Agência');
        $response->assertSee('João Colaborador');
    }

    public function test_collaborator_dashboard_shows_personal_metrics_and_not_admin_view(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'Projeto Alpha',
        ]);
        $project->createDefaultColumns();
        $column = $project->columns()->first();

        // Tarefa do colaborador
        Task::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'project_id' => $project->id,
            'column_id' => $column->id,
            'assignee_id' => $this->member->id,
            'title' => 'Minha Demanda Pessoal',
            'is_published' => false,
            'publish_date' => now()->addDay()->toDateString(),
        ]);

        // Tarefa de outro usuário (Admin)
        Task::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'project_id' => $project->id,
            'column_id' => $column->id,
            'assignee_id' => $this->admin->id,
            'title' => 'Demanda Exclusiva do Admin',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->member)->get(route('dashboard'));
        $response->assertStatus(200);

        // Deve exibir visão pessoal
        $response->assertSee('Espaço Pessoal');
        $response->assertSee('Minha Demanda Pessoal');
        $response->assertSee('Minhas Tarefas Prioritárias');
        $response->assertSee('Minha Produtividade Pessoal');

        // Não deve conter a demanda do admin na fila prioritária
        $response->assertDontSee('Demanda Exclusiva do Admin');
        // Não deve exibir o painel de acompanhamento da equipe
        $response->assertDontSee('Acompanhamento e Evolução da Equipe');
    }

    public function test_admin_dashboard_shows_team_evolution_and_global_metrics(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'Projeto Agência',
        ]);
        $project->createDefaultColumns();
        $column = $project->columns()->first();

        Task::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'project_id' => $project->id,
            'column_id' => $column->id,
            'assignee_id' => $this->member->id,
            'title' => 'Tarefa do João',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Modo Administrador');
        $response->assertSee('Acompanhamento e Evolução da Equipe');
        $response->assertSee('Distribuição & Ritmo da Equipe', false);
        $response->assertSee('João Colaborador');
    }
}
