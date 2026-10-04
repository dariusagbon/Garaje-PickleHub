<?php

namespace App\Http\Controllers;

use App\Services\MonthlyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthlyReportController extends Controller
{
    public function __invoke(Request $request)
    {
        // ?month=2026-10; anything missing or malformed falls back to the current month.
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('month'))
            ? Carbon::createFromFormat('Y-m-d', $request->query('month').'-01')
            : today();

        $report = new MonthlyReport($month);

        return view('admin.reports.monthly', [
            'report' => $report,
            'bookings' => $report->bookings(),
            'bookingSummary' => $report->bookingSummary(),
            'perDay' => $report->bookingsPerDay(),
            'perHour' => $report->bookingsPerHour(),
            'topGuests' => $report->topGuests(),
            'events' => $report->events(),
            'eventSummary' => $report->eventSummary(),
            'previousMonth' => $report->start->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $report->start->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
