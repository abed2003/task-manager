<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCollaborationController extends Controller
{
    public function storeComment(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTaskAccess($task);

        $validated = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $comment = $task->comments()->create($validated + ['user_id' => $request->user()->id]);

        $task->recordActivity('comment', 'أضاف تعليقاً');

        $this->notifyParticipants($task, $request->user(), 'تعليق جديد على مهمة', $task->title.': '.$comment->body);

        return redirect()->to(route('tasks.show', $task).'#comments')->with('success', 'تمت إضافة التعليق.');
    }

    public function destroyComment(Request $request, Task $task, TaskComment $comment): RedirectResponse
    {
        $this->authorizeTaskAccess($task);
        abort_unless($comment->task_id === $task->id, 404);
        abort_unless($request->user()->role === 'admin' || $comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return redirect()->to(route('tasks.show', $task).'#comments')->with('success', 'تم حذف التعليق.');
    }

    public function storeChecklistItem(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTaskAccess($task);

        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $task->checklistItems()->create($validated + [
            'position' => (int) $task->checklistItems()->max('position') + 1,
        ]);

        $task->recordActivity('checklist', 'أضاف بنداً: '.$validated['title']);

        return redirect()->to(route('tasks.show', $task).'#checklist');
    }

    public function toggleChecklistItem(Request $request, Task $task, TaskChecklistItem $item): RedirectResponse
    {
        $this->authorizeTaskAccess($task);
        abort_unless($item->task_id === $task->id, 404);

        $isDone = ! $item->is_done;

        $item->update([
            'is_done' => $isDone,
            'completed_by' => $isDone ? $request->user()->id : null,
            'completed_at' => $isDone ? now() : null,
        ]);

        $task->recordActivity('checklist', ($isDone ? 'أنجز البند: ' : 'أعاد فتح البند: ').$item->title);

        return redirect()->to(route('tasks.show', $task).'#checklist');
    }

    public function destroyChecklistItem(Task $task, TaskChecklistItem $item): RedirectResponse
    {
        $this->authorizeTaskAccess($task);
        abort_unless($item->task_id === $task->id, 404);

        $item->delete();

        return redirect()->to(route('tasks.show', $task).'#checklist');
    }

    public function storeTimeLog(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTaskAccess($task);

        $validated = $request->validate([
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'logged_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $minutes = (int) round($validated['hours'] * 60);

        $task->timeLogs()->create([
            'user_id' => $request->user()->id,
            'minutes' => $minutes,
            'logged_on' => $validated['logged_on'],
            'note' => $validated['note'] ?? null,
        ]);

        $task->recordActivity('time', 'سجّل '.round($minutes / 60, 2).' ساعة عمل');

        return redirect()->to(route('tasks.show', $task).'#time')->with('success', 'تم تسجيل الوقت.');
    }

    public function destroyTimeLog(Request $request, Task $task, TaskTimeLog $timeLog): RedirectResponse
    {
        $this->authorizeTaskAccess($task);
        abort_unless($timeLog->task_id === $task->id, 404);
        abort_unless($request->user()->role === 'admin' || $timeLog->user_id === $request->user()->id, 403);

        $timeLog->delete();

        return redirect()->to(route('tasks.show', $task).'#time')->with('success', 'تم حذف سجل الوقت.');
    }

    private function authorizeTaskAccess(Task $task): void
    {
        abort_unless($task->canBeAccessedBy(auth()->user()), 403);
    }

    /**
     * Notify the assignee and everyone who commented on the task, except the actor.
     */
    private function notifyParticipants(Task $task, User $actor, string $title, string $body): void
    {
        $participantIds = $task->comments()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->push($task->user_id)
            ->filter()
            ->unique()
            ->reject(fn ($id) => (int) $id === $actor->id);

        User::whereIn('id', $participantIds)->get()->each->notify(new WorkspaceNotification(
            $title,
            str($body)->limit(160)->toString(),
            route('tasks.show', $task),
        ));
    }
}
