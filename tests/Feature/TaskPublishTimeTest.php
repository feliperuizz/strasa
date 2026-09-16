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

/**
 * O horário de publicação era validado e aparecia no card, mas nunca era
 * gravado — quem preenchia perdia o valor em silêncio, e os lembretes de
 * publicação (que filtram por publish_time) nunca disparavam.
 */
class TaskPublishTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_horario_de_publicacao_e_salvo_no_card(): void
    {
        $company = Company::create(['name' => 'Ag', 'slug' => 'ag']);
        $user = User::create([
            'company_id' => $company->id, 'name' => 'A', 'email' => 'a@a.test',
            'password' => bcrypt('x'), 'role' => User::ROLE_ADMIN,
        ]);
        $client = Client::create([
            'company_id' => $company->id, 'name' => 'C', 'slug' => 'c',
            'default_columns' => Client::DEFAULT_COLUMNS,
        ]);
        $project = Project::create([
            'company_id' => $company->id, 'client_id' => $client->id, 'name' => 'P', 'slug' => 'p',
        ]);
        $column = Column::create([
            'company_id' => $company->id, 'project_id' => $project->id, 'name' => 'A fazer', 'position' => 1,
        ]);

        $xhr = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

        // Criar já com horário.
        $this->actingAs($user)->withHeaders($xhr)
            ->post(route('tasks.store', $project), [
                'title' => 'Post', 'column_id' => $column->id,
                'publish_date' => '2026-09-20', 'publish_time' => '10:30',
            ])->assertOk();

        $task = Task::first();
        $this->assertSame('10:30', substr((string) $task->publish_time, 0, 5));

        // Editar o horário.
        $this->actingAs($user)->withHeaders($xhr)
            ->patch(route('tasks.update', $task), [
                'title' => 'Post', 'publish_date' => '2026-09-20', 'publish_time' => '15:45',
            ])->assertOk();

        $this->assertSame('15:45', substr((string) $task->fresh()->publish_time, 0, 5));

        // Limpar o horário.
        $this->actingAs($user)->withHeaders($xhr)
            ->patch(route('tasks.update', $task), [
                'title' => 'Post', 'publish_date' => '2026-09-20', 'publish_time' => '',
            ])->assertOk();

        $this->assertNull($task->fresh()->publish_time);
    }
}
