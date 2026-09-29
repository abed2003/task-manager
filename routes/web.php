<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDocumentationController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskCollaborationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// الـ Dashboard بعد تسجيل الدخول
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// Routes للمهام
Route::middleware(['auth'])->group(function () {
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/projects/{project}/documentation', [ProjectDocumentationController::class, 'index'])->name('projects.documentation.index');
    Route::post('/projects/{project}/documentation', [ProjectDocumentationController::class, 'store'])->name('projects.documentation.store');
    Route::patch('/projects/{project}/documentation/{document}', [ProjectDocumentationController::class, 'update'])->name('projects.documentation.update');

    Route::get('/projects/{project}/bugs', [BugReportController::class, 'index'])->name('projects.bugs.index');
    Route::post('/projects/{project}/bugs', [BugReportController::class, 'store'])->name('projects.bugs.store');
    Route::patch('/projects/{project}/bugs/{bugReport}', [BugReportController::class, 'update'])->name('projects.bugs.update');
    Route::post('/projects/{project}/bugs/{bugReport}/comments', [BugReportController::class, 'comment'])->name('projects.bugs.comments.store');

    Route::get('/projects/{project}/sprints', [SprintController::class, 'index'])->name('projects.sprints.index');
    Route::post('/projects/{project}/sprints', [SprintController::class, 'store'])->name('projects.sprints.store');
    Route::patch('/projects/{project}/sprints/{sprint}', [SprintController::class, 'update'])->name('projects.sprints.update');
    Route::patch('/projects/{project}/sprints/{sprint}/tasks/{task}', [SprintController::class, 'updateTaskStatus'])->name('projects.sprints.tasks.status');

    Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::resource('meetings', MeetingController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::resource('tasks', TaskController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::post('/tasks/{task}/comments', [TaskCollaborationController::class, 'storeComment'])->name('tasks.comments.store');
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCollaborationController::class, 'destroyComment'])->name('tasks.comments.destroy');
    Route::post('/tasks/{task}/checklist', [TaskCollaborationController::class, 'storeChecklistItem'])->name('tasks.checklist.store');
    Route::patch('/tasks/{task}/checklist/{item}', [TaskCollaborationController::class, 'toggleChecklistItem'])->name('tasks.checklist.toggle');
    Route::delete('/tasks/{task}/checklist/{item}', [TaskCollaborationController::class, 'destroyChecklistItem'])->name('tasks.checklist.destroy');
    Route::post('/tasks/{task}/time-logs', [TaskCollaborationController::class, 'storeTimeLog'])->name('tasks.time-logs.store');
    Route::delete('/tasks/{task}/time-logs/{timeLog}', [TaskCollaborationController::class, 'destroyTimeLog'])->name('tasks.time-logs.destroy');
    Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggleComplete'])->name('tasks.toggle');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

// Routes للملف الشخصي
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Routes خاصة بالمشرف فقط
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
});

require __DIR__.'/auth.php';
