<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $baseProjectsQuery = Project::accessibleBy($user);

        $projectsQuery = (clone $baseProjectsQuery)
            ->with(['tasks.user', 'creator', 'manager', 'members'])
            ->withCount(['tasks', 'members'])
            ->latest();

        $projectsQuery
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhereHas('manager', fn ($managerQuery) => $managerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('starts_from'), fn ($query) => $query->whereDate('starts_at', '>=', $request->date('starts_from')))
            ->when($request->filled('due_to'), fn ($query) => $query->whereDate('due_at', '<=', $request->date('due_to')));

        $projects = $projectsQuery->paginate(10)->withQueryString();
        $users = User::orderBy('name')->get();

        $stats = [
            'total_projects' => (clone $baseProjectsQuery)->count(),
            'active_projects' => (clone $baseProjectsQuery)->where('status', Project::STATUS_ACTIVE)->count(),
            'completed_projects' => (clone $baseProjectsQuery)->where('status', Project::STATUS_COMPLETED)->count(),
            'on_hold_projects' => (clone $baseProjectsQuery)->where('status', Project::STATUS_ON_HOLD)->count(),
        ];

        return view('projects.index', [
            'projects' => $projects,
            'users' => $users,
            'stats' => $stats,
            'projectStatuses' => Project::statusLabels(),
            'taskStatuses' => Task::statusLabels(),
            'filters' => $request->only(['search', 'status', 'starts_from', 'due_to']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(Project::statusLabels()))],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $validated['manager_id'] ??= $request->user()->id;

        Project::create($validated + ['created_by' => $request->user()->id]);

        return redirect()->route('projects.index')->with('success', 'تم إنشاء المشروع بنجاح.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(Project::statusLabels()))],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'تم تحديث المشروع بنجاح.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeAdmin();

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'تم حذف المشروع ومهامه بنجاح.');
    }

    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();

        abort_unless($project->canManageMembers($user), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(Task::statusLabels()))],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'due_at' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        if ($user->role !== 'admin' && ! in_array((int) $validated['user_id'], $project->assignableUserIds(), true)) {
            throw ValidationException::withMessages([
                'user_id' => 'يجب أن يكون الشخص عضواً في المشروع. أضفه من صفحة أعضاء المشروع أولاً.',
            ]);
        }

        $task = $project->tasks()->create($validated + [
            'is_completed' => $validated['status'] === Task::STATUS_COMPLETED,
        ]);

        $project->addMember($task->user_id, $request->user());
        $task->recordActivity('created', 'أنشأ المهمة ضمن المشروع '.$project->name);

        $task->user?->notify(new WorkspaceNotification(
            'تم تعيين مهمة جديدة لك',
            $task->title,
            route('tasks.show', $task),
        ));

        return back()->with('success', 'تم إسناد المهمة داخل المشروع بنجاح.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
