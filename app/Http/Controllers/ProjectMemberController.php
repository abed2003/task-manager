<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectAccess;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectMemberController extends Controller
{
    use AuthorizesProjectAccess;

    public function index(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->load(['manager', 'members']);

        $taskCounts = $project->tasks()
            ->selectRaw('user_id, count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [Task::STATUS_COMPLETED])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $memberIds = $project->members->pluck('id')->push($project->manager_id)->filter();

        return view('projects.members', [
            'project' => $project,
            'members' => $project->members->sortBy('name'),
            'taskCounts' => $taskCounts,
            'availableUsers' => User::whereNotIn('id', $memberIds)->orderBy('name')->get(),
            'canManage' => $project->canManageMembers(auth()->user()),
            'assignees' => User::whereIn('id', $project->assignableUserIds())->orderBy('name')->get(),
            'taskStatuses' => Task::statusLabels(),
            'priorityLabels' => Task::priorityLabels(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->canManageMembers($request->user()), 403);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        $added = User::whereIn('id', $validated['user_ids'])
            ->where('id', '!=', $project->manager_id)
            ->get()
            ->filter(fn (User $user) => $project->addMember($user, $request->user()));

        $added->reject(fn (User $user) => $user->id === $request->user()->id)
            ->each->notify(new WorkspaceNotification(
                'تمت إضافتك إلى مشروع',
                $project->name,
                route('projects.members.index', $project),
            ));

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', $added->isEmpty() ? 'الأشخاص المختارون أعضاء بالفعل.' : 'تمت إضافة '.$added->count().' عضو إلى المشروع.');
    }

    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        abort_unless($project->canManageMembers($request->user()), 403);

        $project->members()->detach($user->id);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', 'تمت إزالة '.$user->name.' من المشروع.');
    }
}
