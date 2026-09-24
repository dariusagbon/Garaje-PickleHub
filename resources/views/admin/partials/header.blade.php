<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} · PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-page">
    <header class="admin-topbar">
        <a class="auth-logo" href="{{ url('/') }}" aria-label="PickleHub home">
            <img src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo">
            <span>Pickle<span>Hub</span></span>
        </a>
        <div class="admin-topbar-actions">
            <span class="admin-user">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link" type="submit">Log out</button></form>
        </div>
    </header>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <p class="eyebrow eyebrow-dark">Control center</p>
            <nav class="admin-nav" aria-label="Admin navigation">
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="{{ route('admin.events.index') }}">Events</a>
                <a href="{{ url('/') }}">View website</a>
            </nav>
        </aside>
        <main class="admin-content">
