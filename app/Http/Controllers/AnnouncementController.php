<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', [
            'active' => Announcement::active()->with('author')->latest()->get(),
            'expired' => Announcement::expired()->with('author')->latest('expires_at')->take(10)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'nullable|string|max:1000',
        ]);

        Announcement::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('message', 'Announcement posted. It will disappear automatically in '.Announcement::LIFETIME_HOURS.' hours.');
    }

    /** End an announcement early (it stays in the expired list). */
    public function expire(Announcement $announcement)
    {
        if ($announcement->isActive()) {
            $announcement->update(['expires_at' => now()]);
        }

        return back()->with('message', 'Announcement ended. It is no longer shown on the site.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('message', 'Announcement deleted.');
    }
}
