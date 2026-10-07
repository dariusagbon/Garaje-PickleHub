<?php

namespace App\Services;

/**
 * Traditional side-out scoring for a doubles pickleball game.
 *
 * The game is described by the list of rally winners; replaying them from the
 * start gives the score and who is serving, so the state can never break the
 * rules and "undo" is simply dropping the last rally.
 *
 * Rules applied:
 *  - Only the serving team scores. A rally won by the receiving team scores nothing.
 *  - Each team has two servers. When server 1 loses a rally, server 2 serves;
 *    when server 2 loses, it is a side out and the other team's server 1 serves.
 *  - Opening exception: the team serving first starts with only one server
 *    (called "0-0-2"), so its first lost rally is an immediate side out.
 *  - Serve from the right court when the serving team's score is even, left when odd.
 *  - A game is won at 11 points with a lead of at least 2.
 */
class SideOutScoring
{
    public const POINTS_TO_WIN = 11;

    public const WIN_BY = 2;

    /**
     * @param  'A'|'B'  $firstServingTeam
     * @param  array<int, 'A'|'B'>  $rallies  winner of each rally, in order
     * @param  int  $startA  starting score (non-zero only for games scored before rally tracking)
     */
    public static function replay(string $firstServingTeam, array $rallies, int $startA = 0, int $startB = 0): array
    {
        $score = ['A' => $startA, 'B' => $startB];
        $serving = $firstServingTeam;
        // A game picked up mid-way (legacy score) has no opening exception.
        $openingServe = $startA === 0 && $startB === 0;
        $server = $openingServe ? 2 : 1;
        $winner = self::winnerOf($score);

        foreach ($rallies as $rallyWinner) {
            if ($winner !== null) {
                break; // the game is over; nothing after the winning point counts
            }

            if ($rallyWinner === $serving) {
                $score[$serving]++;
                $winner = self::winnerOf($score);

                continue;
            }

            // The receiving team won the rally: no point, the serve moves on.
            if ($openingServe || $server === 2) {
                $serving = self::other($serving);
                $server = 1;
                $openingServe = false;
            } else {
                $server = 2;
            }
        }

        $receiving = self::other($serving);

        return [
            'score_a' => $score['A'],
            'score_b' => $score['B'],
            'serving_team' => $serving,
            'server' => $server,
            'opening_serve' => $openingServe,
            // The score call: serving team's score – receiving team's score – server number.
            'call' => "{$score[$serving]}-{$score[$receiving]}-{$server}",
            'serve_from' => $score[$serving] % 2 === 0 ? 'right' : 'left',
            'complete' => $winner !== null,
            'winner' => $winner,
            'rallies' => count($rallies),
        ];
    }

    /** @return 'A'|'B'|null */
    public static function winnerOf(array $score): ?string
    {
        foreach (['A', 'B'] as $team) {
            $lead = $score[$team] - $score[self::other($team)];
            if ($score[$team] >= self::POINTS_TO_WIN && $lead >= self::WIN_BY) {
                return $team;
            }
        }

        return null;
    }

    public static function other(string $team): string
    {
        return $team === 'A' ? 'B' : 'A';
    }
}
