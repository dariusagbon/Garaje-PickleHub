<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Only admins see who registered for an event. Logged-in users see how many
 * spots are left. Guests see neither.
 */
class EventPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::create([
            'title' => 'Privacy Open',
            'date' => now()->addDay(),
            'time' => '10:00',
            'capacity' => 8,
        ]);

        // Four players: the first is "me", the rest have distinctive names to search for.
        foreach (['Myself Player', 'Secret Sam', 'Hidden Hana', 'Private Pia'] as $i => $name) {
            $user = User::factory()->create(['name' => "Account $i"]);
            $this->event->playerRegistrations()->create(['user_id' => $user->id, 'player_name' => $name]);
            $this->me ??= $user;
        }

        $match = $this->event->matches()->create();
        $ids = $this->event->playerRegistrations()->orderBy('id')->pluck('id');
        $match->players()->attach([$ids[0] => ['team' => 1], $ids[1] => ['team' => 1], $ids[2] => ['team' => 2], $ids[3] => ['team' => 2]]);
    }

    public function test_guests_see_no_names_and_no_spot_counts(): void
    {
        $this->get(route('events.show', $this->event))
            ->assertOk()
            ->assertDontSee('Secret Sam')
            ->assertDontSee('Hidden Hana')
            ->assertDontSee('Myself Player')
            ->assertDontSee('spots left')
            ->assertDontSee('players registered')
            ->assertSee('to see availability');
    }

    public function test_players_see_spots_left_and_their_own_name_but_not_other_players(): void
    {
        $this->actingAs($this->me)
            ->get(route('events.show', $this->event))
            ->assertOk()
            ->assertSee('spots left · 4 of 8 players registered')
            ->assertSee('Myself Player')
            ->assertSee('Your team')
            ->assertDontSee('Secret Sam')
            ->assertDontSee('Hidden Hana')
            ->assertDontSee('Private Pia');
    }

    public function test_admins_see_everyone_who_registered(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('events.show', $this->event))
            ->assertOk()
            ->assertSee('Secret Sam')
            ->assertSee('Hidden Hana')
            ->assertSee('Private Pia')
            ->assertSee('Visible to admins only');
    }

    public function test_home_page_shows_spots_left_only_to_logged_in_users(): void
    {
        $this->get('/')->assertOk()->assertSee('Privacy Open')->assertDontSee('spots left');

        $this->actingAs($this->me)->get('/')->assertOk()->assertSee('4 of 8 spots left');
    }

    public function test_player_dashboard_does_not_show_other_players_names(): void
    {
        EventMatch::first()->update(['score_a' => 11, 'score_b' => 3]);

        $this->actingAs($this->me)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Team A')
            ->assertDontSee('Secret Sam')
            ->assertDontSee('Hidden Hana')
            ->assertDontSee('Private Pia');
    }
}
