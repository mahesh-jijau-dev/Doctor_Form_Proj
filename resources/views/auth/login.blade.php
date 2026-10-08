@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<div class="login-layout">
    <section class="login-aside" aria-label="MediForm information">
        <div class="login-brand">
            <span class="login-brand-icon"><i class="fas fa-heart-pulse" aria-hidden="true"></i></span>
            <span>MediForm</span>
        </div>

        <div class="login-aside-copy">
            <span class="login-eyebrow"><i class="fas fa-shield-heart" aria-hidden="true"></i> Care, connected</span>
            <h1>Better care starts with better information.</h1>
            <p>A secure workspace for your forms, patients, and clinical insights.</p>
        </div>

        <div class="login-aside-footer">
            <span class="login-status-dot"></span>
            <span>Private, secure access for your care team</span>
        </div>
        <span class="login-orbit login-orbit-one" aria-hidden="true"></span>
        <span class="login-orbit login-orbit-two" aria-hidden="true"></span>
    </section>

    <section class="login-panel" aria-labelledby="login-heading">
        <div class="login-form-wrap">
            <div class="login-heading">
                <span class="login-heading-icon"><i class="fas fa-user-doctor" aria-hidden="true"></i></span>
                <p class="login-kicker">Welcome back</p>
                <h2 id="login-heading">Sign in to MediForm</h2>
                <p>Enter your details to access your account.</p>
            </div>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 flex items-start gap-2">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5" aria-hidden="true"></i>
                <div class="text-sm text-red-600 dark:text-red-400">{{ $errors->first() }}</div>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 flex items-start gap-2">
                <i class="fas fa-check-circle text-green-500 mt-0.5" aria-hidden="true"></i>
                <div class="text-sm text-green-600 dark:text-green-400">{{ session('status') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="login-fields">
                <div class="login-field">
                    <label for="email">Email address</label>
                    <div class="login-input-wrap">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               autocomplete="username" class="input-base" placeholder="doctor@hospital.com">
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <div class="login-input-wrap" x-data="{ show: false }">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required
                               autocomplete="current-password" class="input-base" placeholder="Enter your password">
                        <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'"
                                class="login-password-toggle">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="login-remember">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                    <span class="login-secure-note"><i class="fas fa-lock" aria-hidden="true"></i> Secure sign in</span>
                </div>

                <button type="submit" class="login-submit" :disabled="loading">
                    <i class="fas fa-spinner fa-spin" x-show="loading" x-cloak aria-hidden="true"></i>
                    <i class="fas fa-right-to-bracket" x-show="!loading" aria-hidden="true"></i>
                    <span x-text="loading ? 'Signing in...' : 'Sign In'">Sign In</span>
                    <i class="fas fa-arrow-right login-submit-arrow" aria-hidden="true"></i>
                </button>
            </div>
        </form>

            <p class="login-copyright">&copy; {{ date('Y') }} MediForm. All rights reserved.</p>
        </div>
    </section>
</div>
@endsection
