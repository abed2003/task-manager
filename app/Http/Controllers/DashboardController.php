<?php

namespace App\Http\Controllers;

use App\Models\BugReport;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';

        $tasksQuery = $isAdmin
            ? Task::query()
            : $user->tasks();

        $projectsQuery = $isAdmin
            ? Project::query()
            : Project::whereHas('tasks', fn ($query) => $query->where('user_id', $user->id));

        $bugsQuery = $isAdmin
            ? BugReport::query()
            : BugReport::where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhere('reported_by', $user->id)
                    ->orWhereHas('task', fn ($taskQuery) => $taskQuery->where('user_id', $user->id));
            });

        $totalTasks = (clone $tasksQuery)->count();
        $completedTasks = (clone $tasksQuery)->where('status', Task::STATUS_COMPLETED)->count();
        $inProgressTasks = (clone $tasksQuery)->where('status', Task::STATUS_IN_PROGRESS)->count();
        $reviewTasks = (clone $tasksQuery)->where('status', Task::STATUS_REVIEW)->count();
        $todoTasks = (clone $tasksQuery)->where('status', Task::STATUS_TODO)->count();
        $overdueTasks = (clone $tasksQuery)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', now())
            ->count();
        $dueTodayTasks = (clone $tasksQuery)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->whereDate('due_at', now())
            ->count();

        $activeSprint = Sprint::with(['project', 'tasks'])
            ->whereIn('project_id', (clone $projectsQuery)->pluck('id'))
            ->where('status', 'active')
            ->latest()
            ->first();

        $stats = [
            'projects_count' => (clone $projectsQuery)->count(),
            'active_projects' => (clone $projectsQuery)->where('status', Project::STATUS_ACTIVE)->count(),
            'completed_projects' => (clone $projectsQuery)->where('status', Project::STATUS_COMPLETED)->count(),
            'total_tasks' => $totalTasks,
            'todo_tasks' => $todoTasks,
            'in_progress_tasks' => $inProgressTasks,
            'review_tasks' => $reviewTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'due_today_tasks' => $dueTodayTasks,
            'open_bugs' => (clone $bugsQuery)->whereNotIn('status', ['fixed', 'closed'])->count(),
            'completion_percentage' => $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0,
            'recent_tasks' => (clone $tasksQuery)->with(['project', 'user'])->latest()->take(8)->get(),
            'due_tasks' => (clone $tasksQuery)
                ->with(['project', 'user'])
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->whereNotNull('due_at')
                ->orderBy('due_at')
                ->take(6)
                ->get(),
            'recent_projects' => (clone $projectsQuery)
                ->withCount('tasks')
                ->latest()
                ->take(5)
                ->get(),
            'recent_notifications' => $user->notifications()->latest()->take(5)->get(),
            'active_sprint' => $activeSprint,
            'team_count' => $isAdmin ? User::count() : null,
        ];

        $teamWorkload = $isAdmin
            ? User::withCount([
                'tasks as open_tasks_count' => fn ($query) => $query->where('status', '!=', Task::STATUS_COMPLETED),
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', Task::STATUS_COMPLETED),
            ])
                ->orderByDesc('open_tasks_count')
                ->take(5)
                ->get()
            : collect();

        $taskStatusChart = [
            ['label' => 'لم تبدأ', 'count' => $todoTasks, 'color' => '#64748b'],
            ['label' => 'قيد التنفيذ', 'count' => $inProgressTasks, 'color' => '#2563eb'],
            ['label' => 'قيد المراجعة', 'count' => $reviewTasks, 'color' => '#7c3aed'],
            ['label' => 'مكتملة', 'count' => $completedTasks, 'color' => '#059669'],
        ];

        $weeklyProgress = collect(range(6, 0))->map(function (int $daysAgo) use ($tasksQuery) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'count' => (clone $tasksQuery)
                    ->where('status', Task::STATUS_COMPLETED)
                    ->whereDate('updated_at', $date)
                    ->count(),
            ];
        });

        $calendarDays = collect(range(0, 13))->map(function (int $offset) use ($tasksQuery) {
            $date = now()->startOfDay()->addDays($offset);

            return [
                'day' => $date->format('d'),
                'name' => $date->translatedFormat('D'),
                'tasks_due' => (clone $tasksQuery)->whereDate('due_at', $date)->count(),
                'is_today' => $date->isToday(),
            ];
        });

        return view('dashboard', compact('stats', 'user', 'taskStatusChart', 'weeklyProgress', 'calendarDays', 'teamWorkload'));
    }
}
