@extends('layouts.app')

@section('title', 'Admin Login - Digital Queue')

@section('content')
<div class="min-h-screen bg-surface flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <!-- Logo -->
        <div class="text-center mb-8 fade-in">
            <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-card">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-text-main">Admin Access</h1>
            <p class="text-sm text-text-muted mt-1">Enter your PIN to continue</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-card shadow-card p-8 slide-up">
            @if ($errors->any())
                <div class="bg-red-50 border border-red-100 text-red-600 text-sm font-medium rounded-xl p-4 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.authenticate') }}">
                @csrf

                <div class="mb-6">
                    <label for="pin" class="block text-sm font-medium text-text-main mb-2">PIN Code</label>
                    <input
                        type="password"
                        id="pin"
                        name="pin"
                        maxlength="10"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="Enter PIN"
                        class="w-full px-4 py-4 bg-surface border border-gray-200 rounded-xl text-center text-2xl font-bold text-text-main tracking-[0.5em] focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-text-muted placeholder:text-base placeholder:tracking-normal placeholder:font-normal"
                        required
                        autofocus
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-4 rounded-xl transition-all duration-200 scale-press text-base"
                >
                    Unlock Dashboard
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="/" class="text-sm text-text-muted hover:text-primary transition-colors font-medium">
                ← Back to Queue
            </a>
        </div>
    </div>
</div>
@endsection