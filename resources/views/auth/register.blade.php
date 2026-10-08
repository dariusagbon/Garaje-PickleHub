<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create account · PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page min-h-screen bg-[#1b1840] text-[#1b1840]">
    <main class="auth-layout">
        <section class="auth-visual">
            <a class="auth-logo" href="{{ url('/') }}" aria-label="Back to PickleHub home">
                <img src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo">
                <span>Pickle<span>Hub</span></span>
            </a>
            <div class="auth-visual-copy">
                <p class="eyebrow">Your court awaits</p>
                <h1>Find your<br><em>people.</em></h1>
                <p>Join the community to enter events, get randomized into matches, and keep up with every score.</p>
            </div>
            <span class="auth-visual-mark">PH</span>
        </section>
        <section class="auth-panel">
            <div class="auth-form-wrap">
                <a class="auth-back nav-link" href="{{ url('/') }}">&#8592; Back to home</a>
                <p class="eyebrow eyebrow-dark mt-12">Player access</p>
                <h2 class="auth-title">Create account</h2>
                <p class="auth-intro">Make an account to join PickleHub events.</p>
                @if ($errors->any())
                    <div class="auth-alert" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <form class="auth-form" method="POST" action="{{ route('register') }}">
                    @csrf
                    <label for="name">Your name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="button button-dark auth-submit" type="submit">Create account <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="auth-switch">Already a player? <a href="{{ route('login') }}">Log in</a></p>
            </div>
        </section>
    </main>
</body>
</html>
