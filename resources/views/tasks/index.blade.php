@extends('layouts.app')

@section('title', 'Your tasks')

@section('content')
    <section class="intro">
        <p class="eyebrow">YOUR WORKSPACE</p>
        <h1>Make room for what matters.</h1>
        <p class="intro-copy">A simple list, in exactly the right order.</p>
    </section>

    <section class="panel project-panel">
        <div>
            <label class="field-label" for="project-filter">Current project</label>
            <form method="GET" action="{{ route('tasks.index') }}" class="filter-form">
                <select id="project-filter" name="project_id" onchange="this.form.submit()">
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <form method="POST" action="{{ route('projects.store') }}" class="new-project-form">
            @csrf
            <label class="field-label" for="project-name">Add a project</label>
            <div class="inline-form">
                <input id="project-name" name="name" type="text" maxlength="100" placeholder="e.g. Home" required>
                <button class="button button-muted" type="submit">Add project</button>
            </div>
        </form>
    </section>

    @if ($selectedProject)
        <section class="panel create-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">NEW TASK</p>
                    <h2>What needs doing?</h2>
                </div>
                <span class="project-pill">{{ $selectedProject->name }}</span>
            </div>
            <form method="POST" action="{{ route('tasks.store') }}" class="create-form">
                @csrf
                <input type="hidden" name="project_id" value="{{ $selectedProject->id }}">
                <label class="visually-hidden" for="task-name">Task name</label>
                <input id="task-name" name="name" type="text" maxlength="255"
                       value="{{ old('name') }}" placeholder="Write a task to get started..." required>
                <button class="button button-primary" type="submit">Add task <span aria-hidden="true">＋</span></button>
            </form>
        </section>

        <section class="tasks-section">
            <div class="tasks-heading">
                <div>
                    <p class="eyebrow">UP NEXT</p>
                    <h2>{{ $selectedProject->name }} tasks <span class="count">{{ $tasks->count() }}</span></h2>
                </div>
                @if ($tasks->count() > 1)
                    <span class="drag-hint"><span aria-hidden="true">⠿</span> Drag a task to prioritize</span>
                @endif
            </div>

            @if ($tasks->isNotEmpty())
                <ol class="task-list" data-task-list
                    data-reorder-url="{{ route('projects.tasks.reorder', $selectedProject) }}">
                    @foreach ($tasks as $task)
                        <li class="task-row" data-task-id="{{ $task->id }}" draggable="true">
                            <span class="priority">{{ $task->priority }}</span>
                            <span class="drag-handle" aria-label="Drag to reorder" title="Drag to reorder">⠿</span>
                            <div class="task-content">
                                <span class="task-name">{{ $task->name }}</span>
                                <time class="task-date" datetime="{{ $task->created_at->toIso8601String() }}">
                                    Added {{ $task->created_at->format('M j, Y') }}
                                </time>
                            </div>
                            <div class="task-actions">
                                <a class="text-button" href="{{ route('tasks.edit', $task) }}">Edit</a>
                                <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                                      onsubmit="return confirm('Delete this task?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-button text-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <p class="save-status" data-save-status role="status" aria-live="polite"></p>
            @else
                <div class="empty-state">
                    <span class="empty-icon" aria-hidden="true">✦</span>
                    <h3>A fresh start.</h3>
                    <p>Add your first task above. You can drag tasks into priority order anytime.</p>
                </div>
            @endif
        </section>
    @else
        <section class="empty-state project-empty">
            <span class="empty-icon" aria-hidden="true">✦</span>
            <h3>Start with a project.</h3>
            <p>Create a project above to start organizing your tasks.</p>
        </section>
    @endif
@endsection
