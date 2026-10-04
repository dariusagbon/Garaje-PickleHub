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

    private function registerPlayers(Event $event, int $count): array
    {
        return collect(range(1, $count))->map(function ($number) use ($event) {
            $user = User::factory()->create();
            $this->actingAs($user)->post(route('events.register', $event), ['player_name' => "Player $number"])
                ->assertSessionHasNoErrors();
            return $user;
        })->all();
    }

    private function gamesPlayed(Event $event): array
    {
        return $event->playerRegistrations()->get()->mapWithKeys(fn ($registration) => [
            $registration->player_name => \DB::table('event_match_players')->where('event_registration_id', $registration->id)->count(),
        ])->all();
    }

    private function finish(Event $event, EventMatch $match): void
    {
        $this->patch(route('events.matches.score', [$event, $match]), ['score_pin' => '2468', 'score_a' => 11, 'score_b' => 5])
            ->assertSessionHasNoErrors();
    }

    public function test_registering_the_fourth_player_automatically_creates_a_doubles_match(): void
    {
        $event = $this->event();
        $this->registerPlayers($event, 3);
        $this->assertDatabaseCount('event_matches', 0);

        $this->registerPlayers($event, 1);

        $match = EventMatch::sole();
        $this->assertCount(4, $match->players);
        $this->assertSame(2, $match->teamA->count());
        $this->assertSame(2, $match->teamB->count());
    }

    public function test_nobody_plays_twice_until_everyone_has_played(): void
    {
        $event = $this->event();
        $this->registerPlayers($event, 6);
        $first = EventMatch::sole();

        $this->finish($event, $first);

        $second = EventMatch::latest('id')->first();
        $this->assertNotSame($first->id, $second->id);
        // The two players who sat out must be in the next game.
        $firstIds = $first->players->pluck('id');
        $this->assertSame(2, $second->players->pluck('id')->diff($firstIds)->count());
        $this->assertEqualsCanonicalizing([1, 1, 1, 1, 2, 2], array_values($this->gamesPlayed($event)));
    }

    public function test_game_counts_stay_balanced_over_many_rounds(): void
    {
        $event = $this->event(['capacity' => 20]);
        $this->registerPlayers($event, 7);

        for ($round = 0; $round < 12; $round++) {
            $this->finish($event, EventMatch::whereNull('score_a')->firstOrFail());
            $games = $this->gamesPlayed($event);
            $this->assertLessThanOrEqual(1, max($games) - min($games));
        }
    }

    public function test_eight_players_get_two_games_at_once(): void
    {
        $event = $this->event();
        $this->registerPlayers($event, 8);

        $this->assertSame(2, $event->matches()->count());
        $this->assertEquals(array_fill(0, 8, 1), array_values($this->gamesPlayed($event)));
    }

    public function test_admin_reshuffle_keeps_scored_games(): void
    {
        $event = $this->event();
        $this->registerPlayers($event, 6);
        $scored = EventMatch::sole();
        $this->finish($event, $scored);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.events.matches.randomize', $event))->assertRedirect();

        $this->assertDatabaseHas('event_matches', ['id' => $scored->id, 'score_a' => 11]);
        $this->assertSame(2, $event->matches()->count());
    }

    public function test_players_cannot_reshuffle_games(): void
    {
        $event = $this->event();
        $this->actingAs(User::factory()->create())->post(route('admin.events.matches.randomize', $event))->assertForbidden();
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

    public function test_player_dashboard_shows_results_bookings_and_open_events(): void
    {
        $event = $this->event(['title' => 'Tomorrow Social']);
        $other = $this->event(['title' => 'Open Ladder']);
        [$me] = $this->registerPlayers($event, 4);
        $match = EventMatch::sole();
        $myTeam = $match->players->firstWhere('user_id', $me->id)->pivot->team;
        $match->update($myTeam == 1 ? ['score_a' => 11, 'score_b' => 4] : ['score_a' => 4, 'score_b' => 11]);
        \App\Models\Booking::create(['guest_name' => $me->name, 'guest_email' => $me->email, 'booking_date' => now()->addDays(2)->toDateString(), 'court' => 'PickleHub Court', 'hour' => 9, 'status' => 'confirmed']);
        \App\Models\Booking::create(['guest_name' => $me->name, 'guest_email' => $me->email, 'booking_date' => now()->addDays(2)->toDateString(), 'court' => 'PickleHub Court', 'hour' => 10, 'status' => 'confirmed']);

        $this->actingAs($me)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tomorrow Social')
            ->assertSee('Open Ladder')
            ->assertSee('9:00 AM – 11:00 AM')
            ->assertSee('100%')
            ->assertSee('11<i>–</i>4', false);
    }

    public function test_admin_is_redirected_from_player_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
