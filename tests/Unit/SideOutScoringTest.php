<?php

namespace Tests\Unit;

use App\Services\SideOutScoring;
use PHPUnit\Framework\TestCase;

class SideOutScoringTest extends TestCase
{
    private function play(array $rallies, string $first = 'A'): array
    {
        return SideOutScoring::replay($first, $rallies);
    }

    public function test_game_starts_at_zero_zero_two(): void
    {
        $state = $this->play([]);

        $this->assertSame('0-0-2', $state['call']);
        $this->assertSame('A', $state['serving_team']);
        $this->assertSame(2, $state['server']);
        $this->assertSame('right', $state['serve_from']);
    }

    public function test_only_the_serving_team_scores(): void
    {
        $state = $this->play(['A', 'A']);
        $this->assertSame([2, 0], [$state['score_a'], $state['score_b']]);

        // B wins a rally on A's serve: no point for B.
        $state = $this->play(['A', 'A', 'B']);
        $this->assertSame([2, 0], [$state['score_a'], $state['score_b']]);
    }

    public function test_opening_server_loses_and_it_is_an_immediate_side_out(): void
    {
        $state = $this->play(['B']);

        $this->assertSame('B', $state['serving_team']);
        $this->assertSame(1, $state['server']);
        $this->assertSame('0-0-1', $state['call']);
    }

    public function test_server_one_then_server_two_then_side_out(): void
    {
        // Side out to B (opening), then B's server 1 loses, then B's server 2 loses.
        $state = $this->play(['B', 'A']);
        $this->assertSame(['B', 2], [$state['serving_team'], $state['server']]);

        $state = $this->play(['B', 'A', 'A']);
        $this->assertSame(['A', 1], [$state['serving_team'], $state['server']]);
    }

    public function test_score_call_puts_the_serving_team_first(): void
    {
        // A scores 3, side out to B, B scores 1: B is serving at 1-3, server 1.
        $state = $this->play(['A', 'A', 'A', 'B', 'B']);

        $this->assertSame('1-3-1', $state['call']);
        $this->assertSame('left', $state['serve_from']); // B's score is odd
    }

    public function test_game_is_won_at_eleven_by_two(): void
    {
        $state = $this->play(array_fill(0, 11, 'A'));

        $this->assertTrue($state['complete']);
        $this->assertSame('A', $state['winner']);
        $this->assertSame(11, $state['score_a']);
    }

    public function test_game_continues_past_eleven_until_someone_leads_by_two(): void
    {
        // From 10-10 with A serving: 11-10 is not over, 12-10 is.
        $state = SideOutScoring::replay('A', [], 10, 10);
        $this->assertFalse($state['complete']);
        $this->assertSame('A', $state['serving_team']);
        $state = SideOutScoring::replay('A', ['A'], 10, 10);
        $this->assertSame([11, 10, false], [$state['score_a'], $state['score_b'], $state['complete']]);
        $state = SideOutScoring::replay('A', ['A', 'A'], 10, 10);
        $this->assertSame([12, 10, true], [$state['score_a'], $state['score_b'], $state['complete']]);
    }

    public function test_rallies_after_the_winning_point_are_ignored(): void
    {
        $state = $this->play([...array_fill(0, 11, 'A'), 'B', 'B', 'B']);

        $this->assertSame([11, 0], [$state['score_a'], $state['score_b']]);
        $this->assertSame('A', $state['winner']);
    }

    public function test_team_b_can_serve_first(): void
    {
        $state = $this->play(['B', 'B'], first: 'B');

        $this->assertSame([0, 2], [$state['score_a'], $state['score_b']]);
        $this->assertSame('2-0-2', $state['call']);
    }

    public function test_a_game_picked_up_mid_score_has_no_opening_exception(): void
    {
        $state = SideOutScoring::replay('A', ['B'], 5, 3);

        // A's server 1 lost, so A's server 2 serves next (no immediate side out).
        $this->assertSame(['A', 2], [$state['serving_team'], $state['server']]);
    }
}
