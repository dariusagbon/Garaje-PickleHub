<?php

namespace App\Models;

use App\Services\SideOutScoring;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMatch extends Model
{
    /** A game is won by the first team to reach this many points... */
    public const POINTS_TO_WIN = SideOutScoring::POINTS_TO_WIN;

    /** ...with at least this lead. */
    public const WIN_BY = SideOutScoring::WIN_BY;

    protected $fillable = ['event_id', 'score_a', 'score_b', 'first_serving_team'];

    protected $casts = [
        'score_a' => 'integer',
        'score_b' => 'integer',
        'start_score_a' => 'integer',
        'start_score_b' => 'integer',
        'rallies' => 'array',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'first_serving_team' => 'A',
    ];

    protected static function booted(): void
    {
        // Scores autosave during play; the game counts as finished only once
        // the score is a winning one. Correcting it back reopens the game.
        static::saving(function (EventMatch $match) {
            if (! self::isWinningScore($match->score_a, $match->score_b)) {
                $match->completed_at = null;
            } elseif (! $match->completed_at) {
                $match->completed_at = now();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scoring rules
    |--------------------------------------------------------------------------
    */

    /** A team has reached 11 and leads by 2: the game is over. */
    public static function isWinningScore(?int $a, ?int $b): bool
    {
        $high = max((int) $a, (int) $b);
        $low = min((int) $a, (int) $b);

        return $high >= self::POINTS_TO_WIN && $high - $low >= self::WIN_BY;
    }

    /*
    |--------------------------------------------------------------------------
    | Rally-by-rally scoring (side-out rules, see SideOutScoring)
    |--------------------------------------------------------------------------
    */

    /** Score, server and score call for the game as it stands. */
    public function scoring(): array
    {
        if ($this->rallies === null) {
            // No rallies recorded yet: whatever is on the board is the starting point.
            return SideOutScoring::replay($this->first_serving_team, [], (int) $this->score_a, (int) $this->score_b);
        }

        return SideOutScoring::replay(
            $this->first_serving_team,
            $this->rallies,
            $this->start_score_a,
            $this->start_score_b,
        );
    }

    /** Record who won a rally. Returns false if the game is already over. */
    public function recordRally(string $team): bool
    {
        if ($this->scoring()['complete']) {
            return false;
        }

        if ($this->rallies === null) {
            $this->start_score_a = (int) $this->score_a;
            $this->start_score_b = (int) $this->score_b;
        }

        $this->rallies = [...($this->rallies ?? []), $team];

        return $this->applyScoring();
    }

    /** Remove the last recorded rally. Returns false if there is nothing to undo. */
    public function undoRally(): bool
    {
        if (empty($this->rallies)) {
            return false;
        }

        $rallies = $this->rallies;
        array_pop($rallies);
        $this->rallies = $rallies; // stays [] (not null) so the start score is kept

        return $this->applyScoring();
    }

    /** Choose which team serves first. Only possible before the first rally. */
    public function setFirstServingTeam(string $team): bool
    {
        if (! empty($this->rallies)) {
            return false;
        }

        $this->first_serving_team = $team;

        return $this->applyScoring();
    }

    private function applyScoring(): bool
    {
        $state = $this->scoring();
        $this->score_a = $state['score_a'];
        $this->score_b = $state['score_b'];

        return $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships and scopes
    |--------------------------------------------------------------------------
    */

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function players()
    {
        return $this->belongsToMany(
            EventRegistration::class,
            'event_match_players',
            'match_id',
            'event_registration_id',
        )->withPivot('team');
    }

    public function teamA()
    {
        return $this->players()->wherePivot('team', 1);
    }

    public function teamB()
    {
        return $this->players()->wherePivot('team', 2);
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeUnfinished(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function winnerLabel(): ?string
    {
        if (! $this->isComplete()) {
            return null;
        }

        return $this->score_a > $this->score_b ? 'Team A wins' : 'Team B wins';
    }
}
