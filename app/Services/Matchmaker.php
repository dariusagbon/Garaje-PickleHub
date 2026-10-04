<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rotation matchmaking for doubles events.
 *
 * Players who are not in an unscored match form the waiting pool. Whenever
 * four of them are free, a new 2 vs 2 game is created from the players with
 * the fewest games, so nobody plays again until everyone else has had a turn,
 * unless there are not enough fresh players left to fill the court.
 */
class Matchmaker
{
    public const TEAM_SIZE = 2;

    public const PLAYERS_PER_MATCH = self::TEAM_SIZE * 2;

    /** Create as many new games as the free players allow. Returns how many were created. */
    public function fill(Event $event): int
    {
        if ($event->isPast()) {
            return 0;
        }

        return DB::transaction(function () use ($event) {
            Event::whereKey($event->id)->lockForUpdate()->first();
            $created = 0;
            while ($players = $this->nextPlayers($event)) {
                $this->createMatch($event, $players);
                $created++;
            }

            return $created;
        });
    }

    /** Drop games that have no score yet and rebuild them from the current pool. */
    public function reshuffle(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            // Games already in progress keep their teams; only games with no points yet are redrawn.
            $pending = $event->matches()
                ->unfinished()
                ->where(fn ($q) => $q->whereNull('score_a')->orWhere('score_a', 0))
                ->where(fn ($q) => $q->whereNull('score_b')->orWhere('score_b', 0))
                ->pluck('id');

            DB::table('event_match_players')->whereIn('match_id', $pending)->delete();
            $event->matches()->whereIn('id', $pending)->delete();

            return $this->fill($event);
        });
    }

    /**
     * Games played (scored or scheduled) and whether the player is in an unscored game,
     * keyed by registration id.
     *
     * @return Collection<int, object{games:int, active:bool}>
     */
    public function stats(Event $event): Collection
    {
        $rows = DB::table('event_match_players')
            ->join('event_matches', 'event_matches.id', '=', 'event_match_players.match_id')
            ->where('event_matches.event_id', $event->id)
            ->groupBy('event_match_players.event_registration_id')
            ->select('event_match_players.event_registration_id as id')
            ->selectRaw('count(*) as games')
            ->selectRaw('sum(case when event_matches.completed_at is null then 1 else 0 end) as active')
            ->get()
            ->keyBy('id');

        return $event->playerRegistrations()
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [
                $id => (object) [
                    'games' => (int) ($rows[$id]->games ?? 0),
                    'active' => (int) ($rows[$id]->active ?? 0) > 0,
                ],
            ]);
    }

    /** @return Collection<int, EventRegistration>|null */
    private function nextPlayers(Event $event): ?Collection
    {
        $stats = $this->stats($event);
        // Shuffle first so the stable sort breaks ties between equal game counts randomly.
        $free = $event->playerRegistrations()->get()
            ->reject(fn ($registration) => $stats[$registration->id]->active)
            ->shuffle()
            ->sortBy(fn ($registration) => $stats[$registration->id]->games)
            ->values();

        return $free->count() >= self::PLAYERS_PER_MATCH ? $free->take(self::PLAYERS_PER_MATCH) : null;
    }

    private function createMatch(Event $event, Collection $players): void
    {
        $players = $players->shuffle()->values();
        $match = $event->matches()->create();
        $match->players()->attach($players->mapWithKeys(fn ($player, $index) => [
            $player->id => ['team' => $index < self::TEAM_SIZE ? 1 : 2],
        ])->all());
    }
}
