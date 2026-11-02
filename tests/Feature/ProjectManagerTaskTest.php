<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagerTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $member;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'user']);
        $this->member = User::factory()->create(['role' => 'user']);
        $this->project = Project::create([
            'name' => 'CRM',
            'status' => Project::STATUS_ACTIVE,
            'manager_id' => $this->manager->id,
        ]);
        $this->project->addMember($this->member);
    }

    private function taskPayload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Import contacts',
            'user_id' => $this->member->id,
            'status' => Task::STATUS_TODO,
            'priority' => 'high',
        ];
    }

    public function test_manager_can_assign_a_task_to_a_project_member(): void
    {
        $this->actingAs($this->manager)
            ->post(route('projects.tasks.store', $this->project), $this->taskPayload())
            ->assertSessionHasNoErrors();

        $task = Task::firstWhere('title', 'Import contacts');
        $this->assertSame($this->project->id, $task->project_id);
        $this->assertSame($this->member->id, $task->user_id);
        $this->assertSame(1, $this->member->notifications()->count());
    }

    public function test_manager_can_assign_from_the_task_board(): void
    {
        $this->actingAs($this->manager)
            ->post(route('tasks.store'), $this->taskPayload(['project_id' => $this->project->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['title' => 'Import contacts', 'user_id' => $this->member->id]);
    }

    public function test_manager_cannot_assign_to_someone_outside_the_project(): void
    {
        $outsider = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->manager)
            ->post(route('projects.tasks.store', $this->project), $this->taskPayload(['user_id' => $outsider->id]))
            ->assertSessionHasErrors('user_id');

        $this->actingAs($this->manager)
            ->post(route('tasks.store'), $this->taskPayload(['project_id' => $this->project->id, 'user_id' => $outsider->id]))
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('tasks', 0);
        $this->assertFalse($this->project->members()->whereKey($outsider->id)->exists());
    }

    public function test_manager_cannot_assign_in_a_project_they_do_not_manage(): void
    {
        $other = Project::create(['name' => 'ERP', 'status' => Project::STATUS_ACTIVE]);
        $other->addMember($this->member);

        $this->actingAs($this->manager)
            ->post(route('projects.tasks.store', $other), $this->taskPayload())
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->post(route('tasks.store'), $this->taskPayload(['project_id' => $other->id]))
            ->assertSessionHasErrors('project_id');
    }

    public function test_regular_member_cannot_assign_tasks(): void
    {
        $this->actingAs($this->member)
            ->post(route('projects.tasks.store', $this->project), $this->taskPayload())
            ->assertForbidden();

        $this->actingAs($this->member)
            ->post(route('tasks.store'), $this->taskPayload(['project_id' => $this->project->id]))
            ->assertForbidden();
    }

    public function test_manager_sees_and_can_edit_tasks_in_their_project(): void
    {
        $task = $this->project->tasks()->create($this->taskPayload());

        $this->actingAs($this->manager)->get(route('tasks.index'))->assertOk()->assertSee('Import contacts');
        $this->actingAs($this->manager)->get(route('tasks.show', $task))->assertOk();

        $this->actingAs($this->manager)->patch(route('tasks.update', $task), $this->taskPayload([
            'project_id' => $this->project->id,
            'title' => 'Import contacts from CSV',
            'status' => Task::STATUS_IN_PROGRESS,
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Import contacts from CSV', $task->fresh()->title);
    }

    public function test_assignee_cannot_change_task_details_only_status(): void
    {
        $task = $this->project->tasks()->create($this->taskPayload());

        $this->actingAs($this->member)->patch(route('tasks.update', $task), [
            'status' => Task::STATUS_REVIEW,
            'title' => 'Hacked title',
        ])->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame(Task::STATUS_REVIEW, $task->status);
        $this->assertSame('Import contacts', $task->title);
    }
}
