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
     * O upload aceitava só 6 extensões e no máximo 10MB — vídeo de verdade
     * batia nos dois limites.
     */
    public function test_aceita_video_grande_e_tipos_diversos(): void
    {
        config(['filesystems.attachments_disk' => 'local']);
        Storage::fake('local');

        [$user, $task] = $this->cenario();

        // 60MB: acima do antigo teto de 10MB.
        $video = UploadedFile::fake()->create('campanha.mov', 60 * 1024, 'video/quicktime');
        $planilha = UploadedFile::fake()->create('midia.xlsx', 2048);
        $zip = UploadedFile::fake()->create('artes.zip', 5120);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('attachments.store', $task), ['files' => [$video, $planilha, $zip]])
            ->assertStatus(200);

        $this->assertSame(3, $task->attachments()->count());
        $this->assertSame(
            ['artes.zip', 'campanha.mov', 'midia.xlsx'],
            $task->attachments()->pluck('original_name')->sort()->values()->all()
        );
    }

    /**
     * Aceitar qualquer tipo significa que .html/.svg também entram. Eles não
     * podem abrir inline no domínio do app (XSS armazenado).
     */
    public function test_arquivo_perigoso_nao_abre_inline(): void
    {
        config(['filesystems.attachments_disk' => 'local']);
        Storage::fake('local');

        [$user, $task] = $this->cenario();

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('attachments.store', $task), [
                'files' => [UploadedFile::fake()->createWithContent('nota.html', '<script>alert(1)</script>')],
            ])
            ->assertStatus(200);

        $resposta = $this->actingAs($user)->get(route('attachments.show', $task->attachments()->first()));

        $resposta->assertStatus(200);
        $this->assertSame('application/octet-stream', $resposta->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $resposta->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $resposta->headers->get('X-Content-Type-Options'));
    }

    /** Imagem comum continua abrindo inline, senão as miniaturas quebram. */
    public function test_imagem_continua_inline(): void
    {
        config(['filesystems.attachments_disk' => 'local']);
        Storage::fake('local');

        [$user, $task] = $this->cenario();

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('attachments.store', $task), ['files' => [UploadedFile::fake()->image('arte.png')]])
            ->assertStatus(200);

        $att = $task->attachments()->first();
        $this->assertTrue((bool) $att->is_image);

        $resposta = $this->actingAs($user)->get(route('attachments.show', $att));
        $this->assertStringContainsString('image/', (string) $resposta->headers->get('Content-Type'));
        $this->assertNull($resposta->headers->get('Content-Disposition'));
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
