<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_post_appears_on_the_home_page_and_player_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.announcements.store'), [
                'title' => 'Court closed Saturday morning',
                'body' => 'Resurfacing until 11 AM.',
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->get('/')->assertOk()->assertSee('Court closed Saturday morning')->assertSee('Resurfacing until 11 AM.');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Court closed Saturday morning');
    }

    public function test_announcements_expire_after_24_hours(): void
    {
        $announcement = Announcement::create(['title' => 'Free clinic today']);
        $this->assertTrue($announcement->expires_at->between(now()->addHours(24)->subMinute(), now()->addHours(24)->addMinute()));

        $this->travel(23)->hours();
        $this->get('/')->assertSee('Free clinic today');

        $this->travel(2)->hours();
        $this->get('/')->assertDontSee('Free clinic today');
        $this->assertSame(0, Announcement::active()->count());
    }

    public function test_admin_can_end_an_announcement_early(): void
    {
        $announcement = Announcement::create(['title' => 'Rain delay']);

        $this->actingAs($this->admin())
            ->patch(route('admin.announcements.expire', $announcement))
            ->assertRedirect();

        $this->get('/')->assertDontSee('Rain delay');
        $this->assertDatabaseHas('announcements', ['id' => $announcement->id]); // kept in history
    }

    public function test_admin_can_delete_an_announcement(): void
    {
        $announcement = Announcement::create(['title' => 'Typo']);

        $this->actingAs($this->admin())
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_a_title_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.announcements.store'), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_only_admins_can_manage_announcements(): void
    {
        $this->post(route('admin.announcements.store'), ['title' => 'Hacked'])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->post(route('admin.announcements.store'), ['title' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame(0, Announcement::count());
    }

    public function test_admin_page_lists_live_and_expired_announcements(): void
    {
        Announcement::create(['title' => 'Live one']);
        Announcement::create(['title' => 'Old one', 'expires_at' => now()->subHour()]);

        $this->actingAs($this->admin())
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Live one')
            ->assertSee('Old one')
            ->assertSee('Recently expired');
    }
}
