@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-theme-primary mb-4">
            <i class="fas fa-heart-pulse text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-theme-text">MediForm</h1>
        <p class="text-sm text-theme-muted mt-1">Sign in to your account</p>
    </div>

    <div class="card p-8">
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 flex items-start gap-2">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <div class="text-sm text-red-600 dark:text-red-400">{{ $errors->first() }}</div>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 flex items-start gap-2">
                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                <div class="text-sm text-green-600 dark:text-green-400">{{ session('status') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-theme-muted text-sm"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="input-base pl-9" placeholder="doctor@hospital.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-theme-muted text-sm"></i>
                        <input :type="show ? 'text' : 'password'" name="password" required
                               class="input-base pl-9 pr-10" placeholder="Enter your password">
                        <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-text transition-colors">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-theme">
                        <span class="text-sm text-theme-muted">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="loading">
                    <i class="fas fa-spinner fa-spin" x-show="loading" x-cloak></i>
                    <i class="fas fa-right-to-bracket" x-show="!loading"></i>
                    <span x-text="loading ? 'Signing in...' : 'Sign In'">Sign In</span>
                </button>
            </div>
        </form>
    </div>

    <p class="text-center text-xs text-theme-muted mt-6">
        &copy; {{ date('Y') }} MediForm. All rights reserved.
    </p>
</div>
@endsection
