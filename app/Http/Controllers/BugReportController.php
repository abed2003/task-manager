<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectAccess;
use App\Models\BugReport;
use App\Models\Project;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BugReportController extends Controller
{
    use AuthorizesProjectAccess;

    public function index(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $bugs = $project->bugReports()
            ->with(['task', 'assignee', 'reporter', 'comments.user', 'attachments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', $request->severity))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->latest()
            ->get();

        return view('bugs.index', [
            'project' => $project,
            'bugs' => $bugs,
            'tasks' => $project->tasks()->latest()->get(),
            'users' => User::orderBy('name')->get(),
            'severityLabels' => BugReport::severityLabels(),
            'statusLabels' => BugReport::statusLabels(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $validated = $request->validate([
            'task_id' => ['nullable', 'exists:tasks,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reproduction_steps' => ['nullable', 'string'],
            'severity' => ['required', Rule::in(array_keys(BugReport::severityLabels()))],
            'status' => ['required', Rule::in(array_keys(BugReport::statusLabels()))],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $bug = $project->bugReports()->create($validated + ['reported_by' => $request->user()->id]);

        foreach ($request->file('attachments', []) as $file) {
            $bug->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'path' => $file->store("projects/{$project->id}/bugs", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        if ($bug->assignee) {
            $bug->assignee->notify(new WorkspaceNotification(
                'تم تعيين Bug لك',
                $bug->title,
                route('projects.bugs.index', $project),
                $bug->severity,
            ));
        }

        return back()->with('success', 'تم تسجيل المشكلة بنجاح.');
    }

    public function update(Request $request, Project $project, BugReport $bugReport): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        abort_unless($bugReport->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(BugReport::statusLabels()))],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'severity' => ['required', Rule::in(array_keys(BugReport::severityLabels()))],
        ]);

        $validated['fixed_at'] = in_array($validated['status'], ['fixed', 'closed'], true) ? now() : null;

        $bugReport->update($validated);

        return back()->with('success', 'تم تحديث المشكلة.');
    }

    public function comment(Request $request, Project $project, BugReport $bugReport): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        abort_unless($bugReport->project_id === $project->id, 404);

        $validated = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $comment = $bugReport->comments()->create($validated + ['user_id' => $request->user()->id]);

        if ($bugReport->assignee && $bugReport->assigned_to !== $request->user()->id) {
            $bugReport->assignee->notify(new WorkspaceNotification(
                'تعليق جديد على Bug',
                $comment->body,
                route('projects.bugs.index', $project),
            ));
        }

        return back()->with('success', 'تمت إضافة التعليق.');
    }
}
