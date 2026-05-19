<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectAccess;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SprintController extends Controller
{
    use AuthorizesProjectAccess;

    public function index(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $sprints = $project->sprints()->with('tasks.user')->latest()->get();
        $activeSprint = $sprints->firstWhere('status', 'active') ?? $sprints->first();

        return view('sprints.index', [
            'project' => $project,
            'sprints' => $sprints,
            'activeSprint' => $activeSprint,
            'tasks' => $project->tasks()->with('user')->latest()->get(),
            'taskStatuses' => Task::statusLabels(),
            'sprintStatuses' => Sprint::statusLabels(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Sprint::statusLabels()))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'task_ids' => ['nullable', 'array'],
            'task_ids.*' => ['exists:tasks,id'],
        ]);

        $sprint = $project->sprints()->create($validated + ['created_by' => $request->user()->id]);

        if (! empty($validated['task_ids'])) {
            $project->tasks()->whereIn('id', $validated['task_ids'])->update(['sprint_id' => $sprint->id]);
        }

        foreach ($project->tasks()->where('sprint_id', $sprint->id)->with('user')->get()->pluck('user')->filter()->unique('id') as $user) {
            $user->notify(new WorkspaceNotification('Sprint جديد', $sprint->name, route('projects.sprints.index', $project)));
        }

        return back()->with('success', 'تم إنشاء Sprint بنجاح.');
    }

    public function update(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless($sprint->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Sprint::statusLabels()))],
            'review_notes' => ['nullable', 'string'],
            'task_ids' => ['nullable', 'array'],
            'task_ids.*' => ['exists:tasks,id'],
        ]);

        $sprint->update($validated);

        $project->tasks()->where('sprint_id', $sprint->id)->update(['sprint_id' => null]);
        if (! empty($validated['task_ids'])) {
            $project->tasks()->whereIn('id', $validated['task_ids'])->update(['sprint_id' => $sprint->id]);
        }

        return back()->with('success', 'تم تحديث Sprint.');
    }

    public function updateTaskStatus(Request $request, Project $project, Sprint $sprint, Task $task): JsonResponse
    {
        $this->authorizeProjectAccess($project);
        abort_unless($sprint->project_id === $project->id && $task->project_id === $project->id, 404);
        abort_unless(auth()->user()->role === 'admin' || $task->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
        ]);

        $task->update([
            'sprint_id' => $sprint->id,
            'status' => $validated['status'],
            'is_completed' => $validated['status'] === Task::STATUS_COMPLETED,
        ]);

        return response()->json(['message' => 'تم تحديث مهمة Sprint.', 'status' => $task->status]);
    }
}
