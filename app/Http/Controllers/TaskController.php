<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $selectedProjectId = $request->integer('project_id') ?: null;
        $user = auth()->user();

        if (auth()->user()->role === 'admin') {
            $tasksQuery = Task::with(['user', 'project']);
            $projects = Project::orderBy('name')->get();
        } else {
            $tasksQuery = $user->tasks()->with('project');
            $projects = Project::whereHas('tasks', fn ($query) => $query->where('user_id', $user->id))
                ->orderBy('name')
                ->get();
        }

        if ($selectedProjectId) {
            $tasksQuery->where('project_id', $selectedProjectId);
        }

        $tasks = $tasksQuery->latest()->get();
        $selectedProject = $selectedProjectId
            ? $projects->firstWhere('id', $selectedProjectId)
            : null;

        return view('tasks.index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'selectedProjectId' => $selectedProjectId,
            'users' => User::orderBy('name')->get(),
            'taskStatuses' => Task::statusLabels(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $validated = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
            'priority' => ['required', Rule::in(array_keys(Task::priorityLabels()))],
            'due_at' => ['nullable', 'date'],
        ]);

        Task::create($validated + [
            'is_completed' => $validated['status'] === Task::STATUS_COMPLETED,
        ])->user?->notify(new WorkspaceNotification(
            'تم تعيين مهمة جديدة لك',
            $validated['title'],
            route('tasks.index', array_filter(['project_id' => $validated['project_id'] ?? null])),
        ));

        return redirect()
            ->route('tasks.index', array_filter(['project_id' => $request->input('project_id')]))
            ->with('success', 'تم إضافة المهمة بنجاح.');
    }

    public function update(Request $request, Task $task)
    {
        if (auth()->user()->role !== 'admin' && $task->user_id !== auth()->id()) {
            abort(403, 'لا يمكنك تعديل مهمة ليست مخصصة لك.');
        }

        $rules = [
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
        ];

        if (auth()->user()->role === 'admin') {
            $rules += [
                'project_id' => ['nullable', 'exists:projects,id'],
                'user_id' => ['required', 'exists:users,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'priority' => ['required', Rule::in(array_keys(Task::priorityLabels()))],
                'due_at' => ['nullable', 'date'],
            ];
        }

        $validated = $request->validate($rules);
        $validated['is_completed'] = $validated['status'] === Task::STATUS_COMPLETED;

        $task->update($validated);

        return redirect()
            ->route('tasks.index', array_filter(['project_id' => $request->query('project_id')]))
            ->with('success', 'تم تحديث المهمة بنجاح.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        if (auth()->user()->role !== 'admin' && $task->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
        ]);

        $task->update([
            'status' => $validated['status'],
            'is_completed' => $validated['status'] === Task::STATUS_COMPLETED,
        ]);

        if ($task->user_id !== auth()->id()) {
            $task->user?->notify(new WorkspaceNotification(
                'تم تغيير حالة مهمة',
                $task->title.' - '.$task->statusLabel(),
                route('tasks.index', array_filter(['project_id' => $task->project_id])),
            ));
        }

        return response()->json([
            'message' => 'تم تحديث مرحلة المهمة.',
            'status' => $task->status,
            'status_label' => $task->statusLabel(),
            'is_completed' => $task->is_completed,
        ]);
    }

    public function destroy(Task $task)
    {
        if (auth()->user()->role !== 'admin' && $task->user_id !== auth()->id()) {
            abort(403, 'لا يمكنك حذف مهمة ليست مخصصة لك.');
        }

        $task->delete();

        return redirect()
            ->route('tasks.index', array_filter(['project_id' => request()->query('project_id')]))
            ->with('success', 'تم حذف المهمة بنجاح.');
    }

    public function toggleComplete(Task $task)
    {
        if (auth()->user()->role !== 'admin' && $task->user_id !== auth()->id()) {
            abort(403);
        }

        $nextStatus = $task->status === Task::STATUS_COMPLETED
            ? Task::STATUS_IN_PROGRESS
            : Task::STATUS_COMPLETED;

        $task->update([
            'status' => $nextStatus,
            'is_completed' => $nextStatus === Task::STATUS_COMPLETED,
        ]);

        return back()->with('success', 'تم تغيير مرحلة المهمة.');
    }
}
