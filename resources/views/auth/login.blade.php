<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log in · PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page min-h-screen bg-[#14201e] text-[#14201e]">
    <main class="auth-layout">
        <section class="auth-visual">
            <a class="auth-logo" href="{{ url('/') }}" aria-label="Back to PickleHub home">
                <img src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo">
                <span>Pickle<span>Hub</span></span>
            </a>
            <div class="auth-visual-copy">
                <p class="eyebrow">Welcome back to the club</p>
                <h1>Play your<br><em>next game.</em></h1>
                <p>Sign in to register for events, follow matchups, and keep your place in the PickleHub community.</p>
            </div>
            <span class="auth-visual-mark">PH</span>
        </section>
        <section class="auth-panel">
            <div class="auth-form-wrap">
                <a class="auth-back nav-link" href="{{ url('/') }}">&#8592; Back to home</a>
                <p class="eyebrow eyebrow-dark mt-12">Player access</p>
                <h2 class="auth-title">Log in</h2>
                <p class="auth-intro">Welcome back. Enter your details to continue.</p>
                @if ($errors->any())
                    <div class="auth-alert" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <form class="auth-form" method="POST" action="{{ route('login') }}">
                    @csrf
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <label class="auth-checkbox"><input type="checkbox" name="remember"> <span>Remember me</span></label>
                    <button class="button button-dark auth-submit" type="submit">Log in <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="auth-switch">New to PickleHub? <a href="{{ route('register') }}">Create an account</a></p>
            </div>
        </section>
    </main>
</body>
</html>
