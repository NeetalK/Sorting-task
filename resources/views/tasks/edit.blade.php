@extends('layouts.app')

@section('title', 'Edit task')

@section('content')
    <section class="intro">
        <p class="eyebrow">TASK DETAILS</p>
        <h1>Edit your task.</h1>
        <p class="intro-copy">Update the name or move it to another project.</p>
    </section>

    <section class="panel edit-panel">
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="edit-form">
            @csrf
            @method('PUT')
            <label class="field-label" for="task-name">Task name</label>
            <input id="task-name" name="name" type="text" maxlength="255"
                   value="{{ old('name', $task->name) }}" required autofocus>

            <label class="field-label" for="project-id">Project</label>
            <select id="project-id" name="project_id" required>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}"
                        @selected((int) old('project_id', $task->project_id) === $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>

            <div class="edit-meta">
                <span>Created {{ $task->created_at->format('M j, Y \a\t g:i A') }}</span>
                <span>Last updated {{ $task->updated_at->format('M j, Y \a\t g:i A') }}</span>
            </div>
            <div class="form-actions">
                <a class="button button-muted" href="{{ route('tasks.index', ['project_id' => $task->project_id]) }}">Cancel</a>
                <button class="button button-primary" type="submit">Save changes</button>
            </div>
        </form>
    </section>
@endsection
