<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Column;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O histórico do card existia no banco mas nunca aparecia na tela, e os
 * eventos que mais importam para quem acompanha de fora (anexou, fechou o
 * checklist, passou para outra pessoa) nem chegavam a ser gravados.
 */
class TaskActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $chefe;
    private User $leticia;
    private User $outra;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Ag', 'slug' => 'ag']);

        $this->chefe = User::create([
            'company_id' => $this->company->id, 'name' => 'Chefe', 'email' => 'chefe@a.test',
            'password' => bcrypt('x'), 'role' => User::ROLE_ADMIN,
        ]);
        $this->leticia = User::create([
            'company_id' => $this->company->id, 'name' => 'Leticia', 'email' => 'le@a.test',
            'password' => bcrypt('x'), 'role' => User::ROLE_ADMIN,
        ]);
        $this->outra = User::create([
            'company_id' => $this->company->id, 'name' => 'Bruno', 'email' => 'br@a.test',
            'password' => bcrypt('x'), 'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'company_id' => $this->company->id, 'name' => 'C1', 'slug' => 'c1',
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);
        $this->project = Project::create([
            'company_id' => $this->company->id, 'client_id' => $client->id, 'name' => 'P1', 'slug' => 'p1',
        ]);
        $column = Column::create([
            'company_id' => $this->company->id, 'project_id' => $this->project->id,
            'name' => 'A fazer', 'position' => 1,
        ]);
        $this->task = Task::create([
            'company_id' => $this->company->id, 'client_id' => $client->id,
            'project_id' => $this->project->id, 'column_id' => $column->id,
            'title' => 'Post', 'position' => 1,
        ]);
    }

    /** "as vezes a Leticia mexe no card e inclui algo" */
    public function test_anexo_entra_no_historico_com_o_nome_do_arquivo(): void
    {
        config(['filesystems.attachments_disk' => 'local']);
        Storage::fake('local');

        $this->actingAs($this->leticia)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('attachments.store', $this->task), [
                'files' => [UploadedFile::fake()->image('arte-final.jpg')],
            ])->assertOk();

        $log = $this->task->activities()->first();

        $this->assertSame(TaskActivity::TYPE_ATTACHMENT_ADDED, $log->type);
        $this->assertSame($this->leticia->id, $log->user_id);
        $this->assertStringContainsString('arte-final.jpg', $log->description);
    }

    /** "finaliza a parte dela" — fechar o item do checklist. */
    public function test_concluir_item_do_checklist_vira_historico(): void
    {
        $item = $this->task->items()->create([
            'description' => 'Revisar legenda', 'position' => 1, 'is_completed' => false,
        ]);

        $this->actingAs($this->leticia)
            ->patchJson(route('items.update', $item), ['is_completed' => true])
            ->assertOk();

        $log = $this->task->activities()->first();

        $this->assertSame(TaskActivity::TYPE_CHECKLIST_DONE, $log->type);
        $this->assertStringContainsString('Revisar legenda', $log->description);

        // Reenviar o mesmo estado não pode encher o histórico de repetição.
        $this->actingAs($this->leticia)
            ->patchJson(route('items.update', $item), ['is_completed' => true])
            ->assertOk();

        $this->assertSame(1, $this->task->activities()
            ->where('type', TaskActivity::TYPE_CHECKLIST_DONE)->count());
    }

    /** "pq ela joga pra outra pessoa" — tem que dizer para QUEM. */
    public function test_quem_repassa_a_tarefa_registra_para_quem_passou(): void
    {
        $this->task->assignees()->sync([$this->leticia->id]);

        $this->trocarResponsaveis($this->leticia, [$this->outra->id]);

        $log = $this->task->activities()
            ->where('type', TaskActivity::TYPE_ASSIGNEE_CHANGED)->first();

        $this->assertNotNull($log, 'a troca de responsável precisa virar histórico');
        $this->assertSame('passou a tarefa para Bruno', $log->description);
        $this->assertSame('Leticia', $log->meta['sairam']);
    }

    /** Quando um terceiro remaneja, o log diz de quem saiu para quem entrou. */
    public function test_remanejar_entre_outras_pessoas_nomeia_os_dois_lados(): void
    {
        $this->task->assignees()->sync([$this->leticia->id]);

        $this->trocarResponsaveis($this->chefe, [$this->outra->id]);

        $log = $this->task->activities()
            ->where('type', TaskActivity::TYPE_ASSIGNEE_CHANGED)->first();

        $this->assertSame('passou de Leticia para Bruno', $log->description);
    }

    private function trocarResponsaveis(User $autor, array $ids): void
    {
        $this->actingAs($autor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->patch(route('tasks.update', $this->task), [
                'title' => 'Post',
                'has_assignees' => 1,
                'assignees' => $ids,
            ])->assertOk();
    }

    /**
     * O chefe precisa ver tudo isso ao abrir o card — tanto no slideover do
     * quadro (que vem por XHR) quanto na página cheia da tarefa.
     */
    public function test_o_slideover_do_quadro_mostra_a_timeline_de_atividade(): void
    {
        TaskActivity::registrar($this->task, TaskActivity::TYPE_CHECKLIST_DONE,
            'concluiu "Revisar legenda"', [], $this->leticia->id);

        $this->actingAs($this->chefe)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('tasks.edit', $this->task))
            ->assertOk()
            ->assertSee('Atividade')
            ->assertSee('Revisar legenda', false)
            ->assertSee('Leticia');
    }

    public function test_a_pagina_da_tarefa_mostra_a_timeline_de_atividade(): void
    {
        TaskActivity::registrar($this->task, TaskActivity::TYPE_ATTACHMENT_ADDED,
            'anexou "arte-final.jpg"', [], $this->leticia->id);

        $this->actingAs($this->chefe)
            ->get(route('tasks.show', $this->task))
            ->assertOk()
            ->assertSee('Atividade')
            ->assertSee('arte-final.jpg', false);
    }

    /** O card salva sozinho a cada pausa: digitar não pode virar 10 linhas. */
    public function test_edicoes_seguidas_do_mesmo_campo_viram_uma_linha_so(): void
    {
        foreach (['Pos', 'Post ', 'Post de lançamento'] as $titulo) {
            $this->actingAs($this->leticia)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->patch(route('tasks.update', $this->task), ['title' => $titulo])
                ->assertOk();
        }

        $logs = $this->task->activities()
            ->where('type', TaskActivity::TYPE_TITLE_CHANGED)->get();

        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Post de lançamento', $logs->first()->description);
    }

    /** Descrição vazia do Quill ("<p><br></p>") não é uma mudança. */
    public function test_descricao_vazia_do_editor_nao_gera_historico(): void
    {
        $this->actingAs($this->leticia)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->patch(route('tasks.update', $this->task), [
                'title' => 'Post',
                'description' => '<p><br></p>',
            ])->assertOk();

        $this->assertSame(0, $this->task->activities()
            ->where('type', TaskActivity::TYPE_DESCRIPTION_CHANGED)->count());
    }

    /**
     * A resposta do cliente vem do painel, sem usuário no sistema: a timeline
     * tem que mostrar o nome de quem respondeu, não um "Cliente" genérico.
     */
    public function test_resposta_do_cliente_aparece_com_o_nome_de_quem_respondeu(): void
    {
        $log = TaskActivity::registrar($this->task, TaskActivity::TYPE_PUBLISHED,
            'aprovou a peça no painel do cliente', ['author' => 'Marina (Cliente X)']);

        $this->assertNull($log->user_id);
        $this->assertSame('Marina (Cliente X)', $log->authorName());

        $this->actingAs($this->chefe)
            ->get(route('tasks.show', $this->task))
            ->assertOk()
            ->assertSee('Marina (Cliente X)');
    }

    /** "mexe no card e inclui algo": comentar também entra no histórico. */
    public function test_comentario_entra_no_historico(): void
    {
        $this->actingAs($this->leticia)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('comments.store', $this->task), ['body' => 'Subi a arte revisada'])
            ->assertOk();

        $log = $this->task->activities()->first();

        $this->assertSame(TaskActivity::TYPE_COMMENTED, $log->type);
        $this->assertStringContainsString('Subi a arte revisada', $log->meta['trecho']);
    }
}
