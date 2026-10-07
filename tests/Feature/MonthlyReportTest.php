<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Services\MonthlyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    private function book(string $date, int $hour, string $email = 'ana@example.com', string $status = 'confirmed'): void
    {
        Booking::create([
            'guest_name' => 'Ana Cruz',
            'guest_email' => $email,
            'booking_date' => $date,
            'court' => 'PickleHub Court',
            'hour' => $hour,
            'status' => $status,
        ]);
    }

    private function event(string $title, string $date, int $capacity = 8): Event
    {
        return Event::create([
            'title' => $title,
            'date' => $date,
            'time' => '18:00',
            'capacity' => $capacity,
        ]);
    }

    public function test_summary_counts_only_the_selected_month(): void
    {
        $this->book('2026-03-10', 9);
        $this->book('2026-03-10', 10);
        $this->book('2026-03-11', 9, 'ben@example.com');
        $this->book('2026-03-12', 18, 'ben@example.com', 'cancelled');
        $this->book('2026-04-01', 9); // next month, excluded

        $summary = (new MonthlyReport(Carbon::parse('2026-03-01')))->bookingSummary();

        $this->assertSame(3, $summary['confirmed_hours']);
        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(2, $summary['guests']);
        $this->assertSame(17 * 31, $summary['capacity_hours']);
        $this->assertSame('2026-03-10', $summary['busiest_day']->format('Y-m-d'));
        $this->assertSame(9, $summary['busiest_hour']);
    }

    public function test_per_day_breakdown_includes_empty_days(): void
    {
        $this->book('2026-02-03', 9);

        $perDay = (new MonthlyReport(Carbon::parse('2026-02-15')))->bookingsPerDay();

        $this->assertCount(28, $perDay);
        $this->assertSame(1, $perDay[2]['hours']);
        $this->assertSame(0, $perDay[3]['hours']);
    }

    public function test_event_summary_counts_registrations_and_games(): void
    {
        $event = $this->event('March Ladder', '2026-03-20', capacity: 4);
        $this->event('April Social', '2026-04-02');
        $players = User::factory(4)->create();
        foreach ($players as $player) {
            $event->playerRegistrations()->create(['user_id' => $player->id, 'player_name' => $player->name]);
        }
        $event->matches()->create(['score_a' => 11, 'score_b' => 7]);
        $event->matches()->create();

        $summary = (new MonthlyReport(Carbon::parse('2026-03-01')))->eventSummary();

        $this->assertSame(1, $summary['events']);
        $this->assertSame(4, $summary['registrations']);
        $this->assertSame(4, $summary['players']);
        $this->assertSame(1, $summary['games']);
        $this->assertSame(100, $summary['fill_rate']);
        $this->assertSame('March Ladder', $summary['top_event']->title);
    }

    public function test_admin_can_view_the_printable_report_for_a_month(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->book('2026-03-10', 9, 'ana@example.com');
        $this->event('March Ladder', '2026-03-20')
            ->playerRegistrations()
            ->create(['user_id' => User::factory()->create()->id, 'player_name' => 'Smash Pat']);

        $this->actingAs($admin)
            ->get(route('admin.reports.monthly', ['month' => '2026-03']))
            ->assertOk()
            ->assertSee('March 2026')
            ->assertSee('March Ladder')
            ->assertSee('Smash Pat')
            ->assertSee('ana@example.com')
            ->assertSee('window.print()', false);
    }

    public function test_invalid_month_falls_back_to_the_current_month(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.reports.monthly', ['month' => '2026-13']))
            ->assertOk()
            ->assertSee(now()->format('F Y'));
    }

    public function test_players_cannot_view_reports(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.monthly'))
            ->assertForbidden();
    }
}
