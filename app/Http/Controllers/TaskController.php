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
        $isAdmin = $user->role === 'admin';

        if ($isAdmin) {
            $tasksQuery = Task::with(['user', 'project']);
            $projects = Project::orderBy('name')->get();
        } else {
            $tasksQuery = $user->tasks()->with('project');
            $projects = Project::whereHas('tasks', fn ($query) => $query->where('user_id', $user->id))
                ->orderBy('name')
                ->get();
        }

        $filters = $request->only(['search', 'priority', 'assignee', 'due']);

        $tasksQuery
            ->withCount([
                'comments',
                'checklistItems',
                'checklistItems as done_checklist_items_count' => fn ($query) => $query->where('is_done', true),
            ])
            ->when($selectedProjectId, fn ($query) => $query->where('project_id', $selectedProjectId))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when(
                $request->filled('priority') && array_key_exists($request->input('priority'), Task::priorityLabels()),
                fn ($query) => $query->where('priority', $request->input('priority'))
            )
            ->when($isAdmin && $request->filled('assignee'), fn ($query) => $query->where('user_id', $request->integer('assignee')))
            ->when($request->input('due') === 'overdue', fn ($query) => $query
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', now()))
            ->when($request->input('due') === 'today', fn ($query) => $query->whereDate('due_at', now()))
            ->when($request->input('due') === 'week', fn ($query) => $query
                ->whereDate('due_at', '>=', now())
                ->whereDate('due_at', '<=', now()->addDays(7)));

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
            'priorityLabels' => Task::priorityLabels(),
            'filters' => $filters,
        ]);
    }

    public function show(Task $task)
    {
        abort_unless($task->canBeAccessedBy(auth()->user()), 403);

        $task->load([
            'user',
            'project',
            'sprint',
            'comments' => fn ($query) => $query->with('user')->oldest(),
            'checklistItems.completer',
            'timeLogs' => fn ($query) => $query->with('user')->latest('logged_on')->latest('id'),
            'activities' => fn ($query) => $query->with('user')->latest()->latest('id'),
            'bugReports',
        ]);

        return view('tasks.show', [
            'task' => $task,
            'taskStatuses' => Task::statusLabels(),
            'priorityLabels' => Task::priorityLabels(),
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
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        $task = Task::create($validated + [
            'is_completed' => $validated['status'] === Task::STATUS_COMPLETED,
        ]);

        $task->recordActivity('created', 'أنشأ المهمة وأسندها إلى '.($task->user?->name ?? '-'));

        $task->user?->notify(new WorkspaceNotification(
            'تم تعيين مهمة جديدة لك',
            $validated['title'],
            route('tasks.show', $task),
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
                'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            ];
        }

        $validated = $request->validate($rules);
        $validated['is_completed'] = $validated['status'] === Task::STATUS_COMPLETED;

        $task->update($validated);

        if ($task->wasChanged('status')) {
            $task->recordActivity('status', 'غيّر الحالة إلى '.$task->statusLabel());
        }

        if ($task->wasChanged('user_id')) {
            $task->load('user');
            $task->recordActivity('assigned', 'أعاد إسناد المهمة إلى '.($task->user?->name ?? '-'));
            $task->user?->notify(new WorkspaceNotification(
                'تم تعيين مهمة جديدة لك',
                $task->title,
                route('tasks.show', $task),
            ));
        } elseif ($task->wasChanged(['title', 'description', 'project_id', 'priority', 'due_at', 'estimated_hours'])) {
            $task->recordActivity('updated', 'حدّث تفاصيل المهمة');
        }

        $redirect = $request->input('redirect_to') === 'show'
            ? redirect()->route('tasks.show', $task)
            : redirect()->route('tasks.index', array_filter(['project_id' => $request->query('project_id')]));

        return $redirect->with('success', 'تم تحديث المهمة بنجاح.');
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

        if ($task->wasChanged('status')) {
            $task->recordActivity('status', 'غيّر الحالة إلى '.$task->statusLabel());
        }

        if ($task->user_id !== auth()->id()) {
            $task->user?->notify(new WorkspaceNotification(
                'تم تغيير حالة مهمة',
                $task->title.' - '.$task->statusLabel(),
                route('tasks.show', $task),
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

        $task->recordActivity('status', 'غيّر الحالة إلى '.$task->statusLabel());

        return back()->with('success', 'تم تغيير مرحلة المهمة.');
    }
}
