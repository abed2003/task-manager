<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    private function project(array $attributes = []): Project
    {
        return Project::create($attributes + ['name' => 'Mobile App', 'status' => Project::STATUS_ACTIVE]);
    }

    public function test_admin_can_add_members_and_they_are_notified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$first, $second] = User::factory()->count(2)->create(['role' => 'user']);
        $project = $this->project();

        $this->actingAs($admin)
            ->post(route('projects.members.store', $project), ['user_ids' => [$first->id, $second->id]])
            ->assertRedirect(route('projects.members.index', $project));

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $project->members()->pluck('users.id')->all());
        $this->assertSame(1, $first->notifications()->count());
    }

    public function test_adding_an_existing_member_twice_does_not_duplicate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'user']);
        $project = $this->project();

        $this->actingAs($admin)->post(route('projects.members.store', $project), ['user_ids' => [$member->id]]);
        $this->actingAs($admin)->post(route('projects.members.store', $project), ['user_ids' => [$member->id]]);

        $this->assertSame(1, $project->members()->count());
    }

    public function test_project_manager_can_manage_members(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $member = User::factory()->create(['role' => 'user']);
        $project = $this->project(['manager_id' => $manager->id]);

        $this->actingAs($manager)
            ->post(route('projects.members.store', $project), ['user_ids' => [$member->id]])
            ->assertRedirect();
        $this->assertTrue($project->members()->whereKey($member->id)->exists());

        $this->actingAs($manager)
            ->delete(route('projects.members.destroy', [$project, $member]))
            ->assertRedirect();
        $this->assertFalse($project->members()->whereKey($member->id)->exists());
    }

    public function test_regular_members_cannot_add_people(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        $outsider = User::factory()->create(['role' => 'user']);
        $project = $this->project();
        $project->addMember($member);

        $this->actingAs($member)
            ->post(route('projects.members.store', $project), ['user_ids' => [$outsider->id]])
            ->assertForbidden();
    }

    public function test_members_can_open_project_pages_and_outsiders_cannot(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        $outsider = User::factory()->create(['role' => 'user']);
        $project = $this->project();
        $project->addMember($member);

        $this->actingAs($member)->get(route('projects.members.index', $project))->assertOk()->assertSee($member->name);
        $this->actingAs($member)->get(route('projects.bugs.index', $project))->assertOk();
        $this->actingAs($member)->get(route('projects.index'))->assertSee('Mobile App');

        $this->actingAs($outsider)->get(route('projects.members.index', $project))->assertForbidden();
        $this->actingAs($outsider)->get(route('projects.index'))->assertDontSee('Mobile App');
    }

    public function test_assigning_a_project_task_adds_the_assignee_as_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assignee = User::factory()->create(['role' => 'user']);
        $project = $this->project();

        $this->actingAs($admin)->post(route('projects.tasks.store', $project), [
            'title' => 'Setup CI',
            'user_id' => $assignee->id,
            'status' => Task::STATUS_TODO,
            'priority' => 'medium',
        ]);

        $this->assertTrue($project->members()->whereKey($assignee->id)->exists());
    }
}
