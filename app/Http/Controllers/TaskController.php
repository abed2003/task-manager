<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $selectedProjectId = $request->integer('project_id') ?: null;
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';

        $managedProjects = Project::managedBy($user)->with('members')->orderBy('name')->get();
        $isManager = $isAdmin || $managedProjects->isNotEmpty();

        if ($isAdmin) {
            $tasksQuery = Task::with(['user', 'project']);
            $projects = Project::orderBy('name')->get();
        } else {
            // Members see their own tasks; project managers also follow every task in their projects.
            $tasksQuery = Task::with(['user', 'project'])->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereIn('project_id', $managedProjects->pluck('id')));
            $projects = Project::accessibleBy($user)
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
            ->when($isManager && $request->filled('assignee'), fn ($query) => $query->where('user_id', $request->integer('assignee')))
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
            'canAssignTasks' => $isManager,
            'assignableProjects' => $isAdmin ? $projects : $managedProjects,
            'assignableUsers' => $isAdmin
                ? User::orderBy('name')->get()
                : User::whereIn('id', $managedProjects->flatMap->assignableUserIds()->unique())->orderBy('name')->get(),
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
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        abort_unless($isAdmin || Project::managedBy($user)->exists(), 403);

        $validated = $request->validate([
            'project_id' => [$isAdmin ? 'nullable' : 'required', 'exists:projects,id'],
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
            'priority' => ['required', Rule::in(array_keys(Task::priorityLabels()))],
            'due_at' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        $this->ensureAssignmentAllowed($user, $validated['project_id'] ?? null, (int) $validated['user_id']);

        $task = Task::create($validated + [
            'is_completed' => $validated['status'] === Task::STATUS_COMPLETED,
        ]);

        $task->project?->addMember($task->user_id, $request->user());
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
        $user = $request->user();

        abort_unless($task->canBeAccessedBy($user), 403, 'لا يمكنك تعديل مهمة ليست مخصصة لك.');

        $canManage = $task->canBeManagedBy($user);

        $rules = [
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
        ];

        if ($canManage) {
            $rules += [
                'project_id' => [$user->role === 'admin' ? 'nullable' : 'required', 'exists:projects,id'],
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

        if ($canManage) {
            $this->ensureAssignmentAllowed($user, $validated['project_id'] ?? null, (int) $validated['user_id']);
        }

        $task->update($validated);

        if ($task->wasChanged(['user_id', 'project_id'])) {
            $task->load('project');
            $task->project?->addMember($task->user_id, $request->user());
        }

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
        abort_unless($task->canBeAccessedBy(auth()->user()), 403);

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

        if ((int) $task->user_id !== auth()->id()) {
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
        abort_unless($task->canBeAccessedBy(auth()->user()), 403, 'لا يمكنك حذف مهمة ليست مخصصة لك.');

        $task->delete();

        return redirect()
            ->route('tasks.index', array_filter(['project_id' => request()->query('project_id')]))
            ->with('success', 'تم حذف المهمة بنجاح.');
    }

    public function toggleComplete(Task $task)
    {
        abort_unless($task->canBeAccessedBy(auth()->user()), 403);

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

    /**
     * Admins may assign anyone (the assignee joins the project automatically).
     * Project managers may only assign within projects they manage, to that project's members.
     */
    private function ensureAssignmentAllowed(User $actor, ?int $projectId, int $assigneeId): void
    {
        if ($actor->role === 'admin') {
            return;
        }

        $project = Project::managedBy($actor)->find($projectId);

        if (! $project) {
            throw ValidationException::withMessages([
                'project_id' => 'يمكنك إسناد المهام فقط داخل المشاريع التي تديرها.',
            ]);
        }

        if (! in_array($assigneeId, $project->assignableUserIds(), true)) {
            throw ValidationException::withMessages([
                'user_id' => 'يجب أن يكون الشخص عضواً في المشروع. أضفه من صفحة أعضاء المشروع أولاً.',
            ]);
        }
    }
}
