<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Column;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_de_anexo_via_xhr(): void
    {
        config(['filesystems.attachments_disk' => 'local']);
        Storage::fake('local');

        $company = Company::create(['name' => 'Ag', 'slug' => 'ag']);
        $user = User::create([
            'company_id' => $company->id, 'name' => 'Admin', 'email' => 'a@a.test',
            'password' => bcrypt('x'), 'role' => User::ROLE_ADMIN,
        ]);
        $client = Client::create([
            'company_id' => $company->id, 'name' => 'C1', 'slug' => 'c1',
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);
        $project = Project::create([
            'company_id' => $company->id, 'client_id' => $client->id, 'name' => 'P1', 'slug' => 'p1',
        ]);
        $column = Column::create([
            'company_id' => $company->id, 'project_id' => $project->id, 'name' => 'A fazer', 'position' => 1,
        ]);
        $task = Task::create([
            'company_id' => $company->id, 'client_id' => $client->id,
            'project_id' => $project->id, 'column_id' => $column->id,
            'title' => 'Post', 'position' => 1,
        ]);

        // Exatamente o que o overlay faz: POST multipart com header de XHR.
        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('attachments.store', $task), [
                'files' => [UploadedFile::fake()->image('arte.jpg')],
            ]);

        $response->assertStatus(200)->assertJsonStructure(['message']);
        $this->assertSame(1, $task->attachments()->count());

        // E a rota de exibição responde com cache + 304 na revalidação.
        $att = $task->attachments()->first();
        $show = $this->actingAs($user)->get(route('attachments.show', $att));
        $show->assertStatus(200);
        $this->assertStringContainsString('max-age', $show->headers->get('Cache-Control'));

        $etag = $show->headers->get('ETag');
        $this->actingAs($user)
            ->withHeaders(['If-None-Match' => $etag])
            ->get(route('attachments.show', $att))
            ->assertStatus(304);
    }

    /**
     * O slideover posta comentário por AJAX. O controller declarava retorno
     * RedirectResponse e devolvia JSON — TypeError, erro 500.
     */
    public function test_comentario_via_xhr_responde_json(): void
    {
        [$user, $task] = $this->cenario();

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('comments.store', $task), ['body' => 'Ficou ótimo!'])
            ->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertSame(1, $task->comments()->count());
    }

    /** @return array{0: User, 1: Task} */
    private function cenario(): array
    {
        $company = Company::create(['name' => 'Ag', 'slug' => 'ag']);
        $user = User::create([
            'company_id' => $company->id, 'name' => 'Admin', 'email' => 'a@a.test',
            'password' => bcrypt('x'), 'role' => User::ROLE_ADMIN,
        ]);
        $client = Client::create([
            'company_id' => $company->id, 'name' => 'C1', 'slug' => 'c1',
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);
        $project = Project::create([
            'company_id' => $company->id, 'client_id' => $client->id, 'name' => 'P1', 'slug' => 'p1',
        ]);
        $column = Column::create([
            'company_id' => $company->id, 'project_id' => $project->id, 'name' => 'A fazer', 'position' => 1,
        ]);
        $task = Task::create([
            'company_id' => $company->id, 'client_id' => $client->id,
            'project_id' => $project->id, 'column_id' => $column->id,
            'title' => 'Post', 'position' => 1,
        ]);

        return [$user, $task];
    }
}
