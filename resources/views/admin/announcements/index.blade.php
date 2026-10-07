@include('admin.partials.header', ['title' => 'Announcements'])
@use('App\Models\Announcement')

<div class="admin-heading">
    <div>
        <p class="eyebrow eyebrow-dark">Club news</p>
        <h1 class="admin-title">Announcements</h1>
        <p class="admin-subtitle">
            Shown on the home page and every player's dashboard.
            Each one disappears automatically {{ Announcement::LIFETIME_HOURS }} hours after it is posted.
        </p>
    </div>
</div>

@if (session('message'))
    <div class="admin-alert success">{{ session('message') }}</div>
@endif

@if ($errors->any())
    <div class="admin-alert error">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

{{-- ============================== New announcement ============================== --}}
<section class="admin-card admin-form-card mt-8">
    <div class="admin-card-heading">
        <div>
            <p class="eyebrow eyebrow-dark">Post</p>
            <h2>New announcement</h2>
        </div>
    </div>

    <form class="admin-form mt-5" method="POST" action="{{ route('admin.announcements.store') }}">
        @csrf
        <label>
            Title
            <input name="title" maxlength="120" required value="{{ old('title') }}"
                   placeholder="e.g. Court closed tomorrow morning for resurfacing">
        </label>
        <label>
            Message <span class="normal-case tracking-normal text-[#9da098]">(optional)</span>
            <textarea name="body" rows="3" maxlength="1000"
                      placeholder="Add details players should know.">{{ old('body') }}</textarea>
        </label>
        <div class="flex flex-wrap items-center gap-4">
            <button class="button button-dark" type="submit">Post announcement <span aria-hidden="true">📣</span></button>
            <small class="!mt-0">Visible right away until {{ now()->addHours(Announcement::LIFETIME_HOURS)->format('M j, g:i A') }}.</small>
        </div>
    </form>
</section>

{{-- ============================== Live ============================== --}}
<section class="admin-card mt-8">
    <div class="admin-card-heading">
        <div>
            <p class="eyebrow eyebrow-dark">On the site now</p>
            <h2>Live announcements</h2>
        </div>
        <span class="admin-muted">{{ $active->count() }} live</span>
    </div>

    <div class="admin-event-list">
        @forelse ($active as $announcement)
            @php
                $total = Announcement::LIFETIME_HOURS * 3600;
                $left = max(0, now()->diffInSeconds($announcement->expires_at, false));
            @endphp
            <article class="admin-announcement">
                <div class="min-w-0 flex-1">
                    <p class="eyebrow eyebrow-dark">
                        <span class="live-dot" aria-hidden="true"></span>
                        Live · ends {{ $announcement->expires_at->format('M j, g:i A') }}
                    </p>
                    <h3>{{ $announcement->title }}</h3>
                    @if ($announcement->body)
                        <p class="admin-announcement-body">{{ $announcement->body }}</p>
                    @endif
                    <i class="dash-meter max-w-sm" aria-hidden="true"><i style="--fill: {{ round($left / $total * 100) }}%"></i></i>
                    <p class="admin-muted mt-1">
                        Posted {{ $announcement->created_at->format('M j, g:i A') }}
                        @if ($announcement->author) by {{ $announcement->author->name }} @endif
                        · expires {{ $announcement->expires_at->format('M j, g:i A') }}
                    </p>
                </div>
                <div class="admin-actions">
                    <form method="POST" action="{{ route('admin.announcements.expire', $announcement) }}">
                        @csrf
                        @method('PATCH')
                        <button class="button button-outline" type="submit">End now</button>
                    </form>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}"
                          onsubmit="return confirm('Delete this announcement permanently?')">
                        @csrf
                        @method('DELETE')
                        <button class="button button-danger" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="admin-empty">
                <p>No live announcements. Anything you post appears here.</p>
            </div>
        @endforelse
    </div>
</section>

{{-- ============================== Expired ============================== --}}
@if ($expired->isNotEmpty())
    <section class="admin-card mt-8">
        <div class="admin-card-heading">
            <div>
                <p class="eyebrow eyebrow-dark">History</p>
                <h2>Recently expired</h2>
            </div>
        </div>

        <div class="admin-event-list">
            @foreach ($expired as $announcement)
                <article class="admin-announcement is-expired">
                    <div class="min-w-0 flex-1">
                        <p class="eyebrow">Expired {{ $announcement->expires_at->diffForHumans() }}</p>
                        <h3>{{ $announcement->title }}</h3>
                        @if ($announcement->body)
                            <p class="admin-announcement-body">{{ $announcement->body }}</p>
                        @endif
                    </div>
                    <div class="admin-actions">
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}"
                              onsubmit="return confirm('Delete this announcement permanently?')">
                            @csrf
                            @method('DELETE')
                            <button class="button button-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

@include('admin.partials.footer')
