@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-card-accent"></div>
        <div class="auth-card-body">
            <h1 class="auth-title" id="login-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to your recruitment workspace.</p>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                    >
                    <label class="form-check-label" for="remember">Keep me signed in</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>
        </div>
    </section>
@endsection
