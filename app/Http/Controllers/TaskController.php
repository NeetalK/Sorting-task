<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()->orderBy('name')->get();
        $selectedProject = $projects->firstWhere('id', $request->integer('project_id'))
            ?? $projects->first();

        $tasks = $selectedProject
            ? $selectedProject->tasks()->orderBy('priority')->orderBy('id')->get()
            : collect();

        return view('tasks.index', compact('projects', 'selectedProject', 'tasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        DB::transaction(function () use ($project, $validated): void {
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $lastPriority = $project->tasks()->max('priority') ?? 0;
            $project->tasks()->create([
                'name' => $validated['name'],
                'priority' => $lastPriority + 1,
            ]);
        });

        return to_route('tasks.index', ['project_id' => $project->id])
            ->with('status', 'Task created.');
    }

    public function edit(Task $task): View
    {
        $projects = Project::query()->orderBy('name')->get();

        return view('tasks.edit', compact('task', 'projects'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $previousProjectId = $task->project_id;
        $newProject = Project::findOrFail($validated['project_id']);

        DB::transaction(function () use ($task, $validated, $previousProjectId, $newProject): void {
            if ($previousProjectId !== $newProject->id) {
                Project::query()->whereKey($newProject->id)->lockForUpdate()->firstOrFail();
                $lastPriority = $newProject->tasks()->max('priority') ?? 0;
                $task->priority = $lastPriority + 1;
            }

            $task->update([
                'name' => $validated['name'],
                'project_id' => $newProject->id,
                'priority' => $task->priority,
            ]);

            if ($previousProjectId !== $newProject->id) {
                $this->renumberTasks($previousProjectId);
            }
        });

        return to_route('tasks.index', ['project_id' => $newProject->id])
            ->with('status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $projectId = $task->project_id;

        DB::transaction(function () use ($task, $projectId): void {
            $task->delete();
            $this->renumberTasks($projectId);
        });

        return to_route('tasks.index', ['project_id' => $projectId])
            ->with('status', 'Task deleted.');
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array'],
            'task_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $submittedIds = array_map('intval', $validated['task_ids']);
        $currentIds = array_map(
            'intval',
            $project->tasks()->orderBy('priority')->orderBy('id')->pluck('id')->all(),
        );

        sort($submittedIds);
        $expectedIds = $currentIds;
        sort($expectedIds);

        if ($submittedIds !== $expectedIds) {
            return response()->json([
                'message' => 'The task order is invalid. Refresh the page and try again.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $project): void {
            foreach ($validated['task_ids'] as $index => $taskId) {
                $project->tasks()->whereKey($taskId)->update([
                    'priority' => $index + 1,
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json(['message' => 'Task order saved.']);
    }

    private function renumberTasks(int $projectId): void
    {
        $taskIds = Task::query()
            ->where('project_id', $projectId)
            ->orderBy('priority')
            ->orderBy('id')
            ->pluck('id');

        foreach ($taskIds as $index => $taskId) {
            Task::query()->whereKey($taskId)->update([
                'priority' => $index + 1,
                'updated_at' => now(),
            ]);
        }
    }
}
