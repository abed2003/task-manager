<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function taskFor(User $user, array $attributes = []): Task
    {
        return Task::create($attributes + [
            'title' => 'Build login page',
            'user_id' => $user->id,
            'status' => Task::STATUS_TODO,
            'priority' => 'medium',
        ]);
    }

    public function test_assignee_can_view_task_details(): void
    {
        $member = $this->member();
        $task = $this->taskFor($member);

        $this->actingAs($member)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Build login page');
    }

    public function test_other_members_cannot_view_task_details(): void
    {
        $task = $this->taskFor($this->member());

        $this->actingAs($this->member())->get(route('tasks.show', $task))->assertForbidden();
    }

    public function test_comment_notifies_assignee_and_logs_activity(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $task = $this->taskFor($member);

        $this->actingAs($admin)
            ->post(route('tasks.comments.store', $task), ['body' => 'Please add validation'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'body' => 'Please add validation']);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'type' => 'comment', 'user_id' => $admin->id]);
        $this->assertSame(1, $member->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_checklist_items_can_be_added_and_toggled(): void
    {
        $member = $this->member();
        $task = $this->taskFor($member);

        $this->actingAs($member)->post(route('tasks.checklist.store', $task), ['title' => 'Write tests']);
        $item = $task->checklistItems()->first();
        $this->assertFalse($item->is_done);

        $this->actingAs($member)->patch(route('tasks.checklist.toggle', [$task, $item]));

        $item->refresh();
        $this->assertTrue($item->is_done);
        $this->assertSame($member->id, $item->completed_by);
    }

    public function test_time_can_be_logged_against_a_task(): void
    {
        $member = $this->member();
        $task = $this->taskFor($member, ['estimated_hours' => 4]);

        $this->actingAs($member)->post(route('tasks.time-logs.store', $task), [
            'hours' => 1.5,
            'logged_on' => now()->toDateString(),
            'note' => 'Initial layout',
        ])->assertRedirect();

        $this->assertSame(1.5, $task->fresh()->loggedHours());
    }

    public function test_members_cannot_delete_other_users_comments(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $task = $this->taskFor($member);
        $comment = $task->comments()->create(['user_id' => $admin->id, 'body' => 'Admin note']);

        $this->actingAs($member)
            ->delete(route('tasks.comments.destroy', [$task, $comment]))
            ->assertForbidden();
    }

    public function test_status_change_is_recorded_in_activity_log(): void
    {
        $member = $this->member();
        $task = $this->taskFor($member);

        $this->actingAs($member)
            ->patchJson(route('tasks.status', $task), ['status' => Task::STATUS_IN_PROGRESS])
            ->assertOk();

        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'type' => 'status']);
    }

    public function test_board_can_be_filtered_by_search_and_overdue(): void
    {
        $member = $this->member();
        $this->taskFor($member, ['title' => 'Fix payment gateway', 'due_at' => now()->subDays(3)]);
        $this->taskFor($member, ['title' => 'Design landing page', 'due_at' => now()->addDays(3)]);

        $this->actingAs($member)->get(route('tasks.index', ['search' => 'payment']))
            ->assertOk()
            ->assertSee('Fix payment gateway')
            ->assertDontSee('Design landing page');

        $this->actingAs($member)->get(route('tasks.index', ['due' => 'overdue']))
            ->assertOk()
            ->assertSee('Fix payment gateway')
            ->assertDontSee('Design landing page');
    }
}
