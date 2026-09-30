<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_seeded_admin_can_sign_in_and_there_is_no_registration_route(): void
    {
        $this->seed();

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'DevLom-Admin!2026',
        ])->assertOk()->assertJsonPath('user.email', 'admin@example.com');

        $this->getJson('/api/user')->assertOk()->assertJsonPath('user.name', 'Admin');
        $this->postJson('/api/logout')->assertOk();
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/register')->assertNotFound();
    }

    public function test_spa_routes_load_directly_without_matching_api_paths(): void
    {
        foreach (['/clients', '/collaborators', '/projects', '/tasks'] as $path) {
            $this->get($path)->assertOk()->assertViewIs('app');
        }

        $this->get('/api/unknown')->assertNotFound();
    }

    public function test_tasks_receive_project_folios_and_time_is_costed_and_locked_when_finished(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@example.com')->first());
        $client = Client::create(['name' => 'Acme', 'rfc' => 'ACME123']);
        $project = Project::create([
            'name' => 'Website',
            'alias' => 'WEB',
            'client_id' => $client->id,
        ]);
        $collaborator = Collaborator::create(['name' => 'Developer', 'price' => 125.50]);

        $firstTask = $this->postJson('/api/tasks', [
            'description' => 'Build a page',
            'project_id' => $project->id,
        ])->assertCreated()
            ->assertJsonPath('folio', 'WEB-1')
            ->assertJsonPath('status.code', 'PRC')
            ->json('id');
        $secondTask = $this->postJson('/api/tasks', [
            'description' => 'Add validation',
            'project_id' => $project->id,
        ])->assertCreated()->assertJsonPath('folio', 'WEB-2')->json('id');

        $this->putJson('/api/projects/'.$project->id, [
            'name' => 'Website Revamp',
            'alias' => 'LOCK',
            'client_id' => $client->id + 1,
        ])->assertOk()->assertJsonPath('alias', 'WEB')->assertJsonPath('client_id', $client->id);

        $this->postJson('/api/time-entries', [
            'task_id' => $firstTask,
            'collaborator_id' => $collaborator->id,
            'hours' => 1.5,
        ])->assertCreated()->assertJsonPath('total', '188.25');

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.1.folio', 'WEB-1')
            ->assertJsonPath('data.1.total_hours', 1.5)
            ->assertJsonPath('data.1.total_cost', 188.25)
            ->assertJsonPath('data.1.time_entries_count', 1);

        $this->patchJson('/api/tasks/'.$firstTask.'/finish')
            ->assertOk()
            ->assertJsonPath('status.code', 'FIN');
        $this->postJson('/api/time-entries', [
            'task_id' => $firstTask,
            'collaborator_id' => $collaborator->id,
            'hours' => 1,
        ])->assertUnprocessable();
        $this->deleteJson('/api/tasks/'.$firstTask)->assertStatus(409);
        $this->deleteJson('/api/tasks/'.$secondTask)->assertOk();
    }

    public function test_inactive_associations_and_out_of_range_prices_are_rejected(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@example.com')->first());
        $client = Client::create(['name' => 'Dormant', 'rfc' => 'DORMANT', 'active' => false]);

        $this->postJson('/api/projects', [
            'name' => 'Hidden project',
            'alias' => 'HIDE',
            'client_id' => $client->id,
        ])->assertUnprocessable();

        $this->postJson('/api/collaborators', [
            'name' => 'Too expensive',
            'price' => 1000.01,
        ])->assertUnprocessable();
    }
}
