@include('admin.partials.header', ['title' => $event->exists ? 'Edit event' : 'New event'])

<div class="admin-heading">
    <div>
        <p class="eyebrow eyebrow-dark">Event setup</p>
        <h1 class="admin-title">{{ $event->exists ? 'Edit event' : 'New event' }}</h1>
        <p class="admin-subtitle">Set the schedule and registration capacity.</p>
    </div>
    <a class="button button-outline" href="{{ route('admin.events.index') }}">&#8592; Events</a>
</div>

<section class="admin-card admin-form-card mt-8">
    @if ($errors->any())
        <div class="admin-alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="admin-form" method="POST"
          action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}">
        @csrf
        @if ($event->exists)
            @method('PUT')
        @endif

        <div class="admin-form-grid">
            <label>
                Event title
                <input name="title" value="{{ old('title', $event->title) }}" required>
            </label>
            <label>
                Capacity
                <input name="capacity" type="number" min="1" value="{{ old('capacity', $event->capacity ?: 20) }}" required>
            </label>
            <label>
                Date
                <input name="date" type="date" value="{{ old('date', $event->date?->format('Y-m-d')) }}" required>
            </label>
            <label>
                Start time
                <input name="time" type="time" value="{{ old('time', $event->time) }}" required>
            </label>
        </div>

        <label>
            Description
            <textarea name="description" rows="4">{{ old('description', $event->description) }}</textarea>
        </label>

        <button class="button button-dark" type="submit">
            {{ $event->exists ? 'Save changes' : 'Create event' }} <span aria-hidden="true">&#8594;</span>
        </button>
    </form>
</section>

@include('admin.partials.footer')
