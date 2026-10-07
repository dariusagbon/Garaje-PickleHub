{{--
    Live announcements banner. $announcements is provided by the view composer in
    AppServiceProvider (only ones that have not expired). Used on the home page
    and the player dashboard. Visitors can hide one on their device with ✕.
--}}
@if ($announcements->isNotEmpty())
    <section class="announcements" aria-label="Announcements">
        @foreach ($announcements as $announcement)
            <article class="announcement" data-announcement="{{ $announcement->id }}">
                <span class="announcement-icon" aria-hidden="true">📣</span>
                <div class="min-w-0 flex-1">
                    <p class="announcement-title">{{ $announcement->title }}</p>
                    @if ($announcement->body)
                        <p class="announcement-body">{{ $announcement->body }}</p>
                    @endif
                    <p class="announcement-meta">
                        Posted {{ $announcement->created_at->diffForHumans() }}
                        · ends {{ $announcement->expires_at->format('M j, g:i A') }}
                    </p>
                </div>
                <button class="announcement-close" type="button" data-dismiss-announcement aria-label="Hide this announcement">✕</button>
            </article>
        @endforeach
    </section>
@endif
