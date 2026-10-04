<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPlayerTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Saturday Social',
            'date' => now()->addDay(),
            'time' => '10:00',
            'capacity' => 8,
            'score_pin' => \Illuminate\Support\Facades\Hash::make('2468'),
        ], $attributes));
    }

    public function test_authenticated_player_can_register_and_update_their_display_name(): void
    {
        $event = $this->event();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('events.register', $event), ['player_name' => 'Ace Player'])
            ->assertRedirect();
        $this->assertDatabaseHas('event_registrations', ['event_id' => $event->id, 'user_id' => $user->id, 'player_name' => 'Ace Player']);

        $this->actingAs($user)->post(route('events.register', $event), ['player_name' => 'Updated Name'])
            ->assertRedirect();
        $this->assertDatabaseHas('event_registrations', ['event_id' => $event->id, 'user_id' => $user->id, 'player_name' => 'Updated Name']);
    }

    public function test_admin_can_randomize_four_registered_players_into_a_doubles_match(): void
    {
        $event = $this->event();
        foreach (range(1, 4) as $number) {
            $user = User::factory()->create();
            $event->playerRegistrations()->create(['user_id' => $user->id, 'player_name' => "Player $number"]);
        }
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.events.matches.randomize', $event))->assertRedirect();
        $match = EventMatch::first();
        $this->assertNotNull($match);
        $this->assertCount(4, $match->players);
        $this->assertSame(2, $match->teamA->count());
        $this->assertSame(2, $match->teamB->count());
    }

    public function test_guest_can_generate_initial_matches(): void
    {
        $event = $this->event();
        foreach (range(1, 4) as $number) {
            $user = User::factory()->create();
            $event->playerRegistrations()->create(['user_id' => $user->id, 'player_name' => "Player $number"]);
        }

        $this->post(route('events.matches.randomize', $event))->assertRedirect();

        $this->assertDatabaseCount('event_matches', 1);
    }

    public function test_scores_must_reach_eleven_and_win_by_two(): void
    {
        $event = $this->event();
        $admin = User::factory()->create(['is_admin' => true]);
        $match = $event->matches()->create();

        $this->actingAs($admin)->patch(route('events.matches.score', [$event, $match]), ['score_pin' => '2468', 'score_a' => 11, 'score_b' => 10])
            ->assertSessionHasErrors('score_a');
        $this->assertDatabaseHas('event_matches', ['id' => $match->id, 'score_a' => null, 'score_b' => null]);

        $this->actingAs($admin)->patch(route('events.matches.score', [$event, $match]), ['score_pin' => '2468', 'score_a' => 12, 'score_b' => 10])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('event_matches', ['id' => $match->id, 'score_a' => 12, 'score_b' => 10]);
    }

    public function test_guest_can_update_a_match_score(): void
    {
        $event = $this->event();
        $match = $event->matches()->create();

        $this->patch(route('events.matches.score', [$event, $match]), [
            'score_pin' => '2468',
            'score_a' => 11,
            'score_b' => 7,
        ])->assertRedirect();

        $this->assertDatabaseHas('event_matches', [
            'id' => $match->id,
            'score_a' => 11,
            'score_b' => 7,
        ]);
    }

    public function test_past_events_are_hidden_from_the_player_dashboard(): void
    {
        $user = User::factory()->create();
        $upcoming = $this->event(['title' => 'Tomorrow Social']);
        $past = $this->event(['title' => 'Last Week Social', 'date' => now()->subWeek()]);
        $upcoming->users()->attach($user);
        $past->users()->attach($user);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tomorrow Social')
            ->assertDontSee('Last Week Social');
        $this->get('/')->assertSee('Tomorrow Social')->assertDontSee('Last Week Social');
    }

    public function test_players_cannot_register_for_past_events(): void
    {
        $event = $this->event(['date' => now()->subDay()]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('events.register', $event), ['player_name' => 'Late Player'])
            ->assertSessionHasErrors('event');
        $this->assertDatabaseMissing('event_registrations', ['event_id' => $event->id]);
    }

    public function test_admin_event_history_lists_past_events_and_who_joined(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Pat Player', 'email' => 'pat@example.com']);
        $past = $this->event(['title' => 'Last Week Social', 'date' => now()->subWeek()]);
        $this->event(['title' => 'Tomorrow Social']);
        $past->playerRegistrations()->create(['user_id' => $player->id, 'player_name' => 'Smash Pat']);

        $this->actingAs($admin)->get(route('admin.events.history'))
            ->assertOk()
            ->assertSee('Last Week Social')
            ->assertSee('Smash Pat')
            ->assertSee('pat@example.com')
            ->assertDontSee('Tomorrow Social');

        $this->actingAs($admin)->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('Tomorrow Social')
            ->assertDontSee('Last Week Social');
    }

    public function test_players_cannot_view_event_history(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.events.history'))->assertForbidden();
    }
}
