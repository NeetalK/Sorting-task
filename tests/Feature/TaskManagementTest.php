<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_are_created_and_listed_for_the_selected_project(): void
    {
        $project = Project::create(['name' => 'Website']);
        $otherProject = Project::create(['name' => 'Home']);
        $project->tasks()->create(['name' => 'Earlier task', 'priority' => 1]);

        $this->post(route('tasks.store'), [
            'name' => 'Write launch notes',
            'project_id' => $project->id,
        ])->assertRedirect(route('tasks.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('tasks', [
            'name' => 'Write launch notes',
            'project_id' => $project->id,
            'priority' => 2,
        ]);
        $this->get(route('tasks.index', ['project_id' => $otherProject->id]))
            ->assertOk()
            ->assertDontSee('Write launch notes');
    }

    public function test_projects_can_be_created_from_the_workspace(): void
    {
        $this->post(route('projects.store'), ['name' => 'Side project'])
            ->assertRedirect(route('tasks.index', [
                'project_id' => Project::where('name', 'Side project')->value('id'),
            ]));

        $this->assertDatabaseHas('projects', ['name' => 'Side project']);
    }

    public function test_reordering_saves_consecutive_priorities_within_a_project(): void
    {
        $project = Project::create(['name' => 'Website']);
        $first = $project->tasks()->create(['name' => 'First', 'priority' => 1]);
        $second = $project->tasks()->create(['name' => 'Second', 'priority' => 2]);

        $this->postJson(route('projects.tasks.reorder', $project), [
            'task_ids' => [$second->id, $first->id],
        ])->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $second->id, 'priority' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $first->id, 'priority' => 2]);
    }

    public function test_reordering_rejects_task_ids_from_another_or_missing_task(): void
    {
        $project = Project::create(['name' => 'Website']);
        $task = $project->tasks()->create(['name' => 'Task', 'priority' => 1]);
        $otherProject = Project::create(['name' => 'Home']);
        $otherTask = $otherProject->tasks()->create(['name' => 'Other task', 'priority' => 1]);

        $this->postJson(route('projects.tasks.reorder', $project), [
            'task_ids' => [$otherTask->id],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'priority' => 1]);
    }

    public function test_moving_a_task_appends_it_and_closes_the_old_priority_gap(): void
    {
        $from = Project::create(['name' => 'From']);
        $to = Project::create(['name' => 'To']);
        $moving = $from->tasks()->create(['name' => 'Move me', 'priority' => 1]);
        $remaining = $from->tasks()->create(['name' => 'Stay', 'priority' => 2]);
        $to->tasks()->create(['name' => 'Existing', 'priority' => 1]);

        $this->put(route('tasks.update', $moving), [
            'name' => $moving->name,
            'project_id' => $to->id,
        ])->assertRedirect(route('tasks.index', ['project_id' => $to->id]));

        $this->assertDatabaseHas('tasks', ['id' => $moving->id, 'project_id' => $to->id, 'priority' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $remaining->id, 'priority' => 1]);
    }

    public function test_deleting_a_task_closes_its_priority_gap(): void
    {
        $project = Project::create(['name' => 'Website']);
        $first = $project->tasks()->create(['name' => 'First', 'priority' => 1]);
        $second = $project->tasks()->create(['name' => 'Second', 'priority' => 2]);

        $this->delete(route('tasks.destroy', $first))
            ->assertRedirect(route('tasks.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('tasks', ['id' => $second->id, 'priority' => 1]);
    }
}
