<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('theme') !== 'light', menu: false }" :class="{ 'dark': dark }">

<head>
    <script>
        (() => {
            try {
                if (localStorage.getItem('theme') !== 'light') document.documentElement.classList.add('dark');
            } catch (e) {}
            document.documentElement.classList.add('js');
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Doctor Forms') }} – Secure forms for healthcare</title>
    <meta name="description"
        content="Fill out clinic and hospital forms online in minutes. Secure, simple and paperless.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5rem
        }

        body {
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif
        }

        .h-display {
            font-weight: 800;
            letter-spacing: -.035em;
            line-height: 1.05
        }

        .h-sec {
            font-weight: 800;
            letter-spacing: -.025em
        }

        /* top scroll progress bar */
        #progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 100%;
            z-index: 60;
            transform-origin: 0 50%;
            transform: scaleX(0);
            background: linear-gradient(90deg, rgb(var(--color-primary)), rgb(var(--color-primary-light)))
        }

        /* background grid + aurora */
        .lp-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate
        }

        .lp-grid {
            position: absolute;
            inset: 0;
            z-index: -2;
            background-image: linear-gradient(rgb(var(--color-border)/.55) 1px, transparent 1px), linear-gradient(90deg, rgb(var(--color-border)/.55) 1px, transparent 1px);
            background-size: 48px 48px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 80%);
            mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 80%)
        }

        .blob {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .45;
            animation: drift 14s ease-in-out infinite alternate
        }

        .blob.a {
            width: 28rem;
            height: 28rem;
            left: -8rem;
            top: -6rem;
            background: rgb(var(--color-primary))
        }

        .blob.b {
            width: 24rem;
            height: 24rem;
            right: -6rem;
            top: 2rem;
            background: rgb(var(--color-accent));
            animation-delay: -5s
        }

        .blob.c {
            width: 18rem;
            height: 18rem;
            left: 40%;
            bottom: -8rem;
            background: rgb(var(--color-info));
            opacity: .28;
            animation-delay: -9s
        }

        @keyframes drift {
            to {
                transform: translate(40px, 30px) scale(1.12)
            }
        }

        /* gradient headline */
        .grad-text {
            background: linear-gradient(90deg, rgb(var(--color-primary)), rgb(var(--color-primary-light)), rgb(var(--color-info)), rgb(var(--color-primary)));
            background-size: 250% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: shine 6s linear infinite
        }

        @keyframes shine {
            to {
                background-position: -250% 0
            }
        }

        /* hero load-in (staggered) */
        .js .hin {
            opacity: 0;
            transform: translateY(22px);
            animation: hin .7s cubic-bezier(.2, .7, .2, 1) forwards;
            animation-delay: calc(var(--i, 0)*110ms)
        }

        @keyframes hin {
            to {
                opacity: 1;
                transform: none
            }
        }

        /* scroll reveal */
        .js .rv {
            opacity: 0;
            transform: translateY(26px);
            transition: opacity .7s cubic-bezier(.2, .7, .2, 1), transform .7s cubic-bezier(.2, .7, .2, 1);
            transition-delay: calc(var(--d, 0)*90ms)
        }

        .js .rv.in {
            opacity: 1;
            transform: none
        }

        /* pill badge with live dot */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .35rem .9rem .35rem .45rem;
            border-radius: 999px;
            border: 1px solid rgb(var(--color-border));
            background: rgb(var(--color-surface)/.8);
            font-size: .8rem;
            font-weight: 600;
            backdrop-filter: blur(8px)
        }

        .pill b {
            background: rgb(var(--color-primary));
            color: #fff;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .7rem
        }

        .live {
            position: relative;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgb(var(--color-success))
        }

        .live::after {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid rgb(var(--color-success));
            animation: ping 1.6s ease-out infinite
        }

        @keyframes ping {
            from {
                transform: scale(.5);
                opacity: .9
            }

            to {
                transform: scale(1.6);
                opacity: 0
            }
        }

        /* demo window */
        .win {
            background: rgb(var(--color-surface));
            border: 1px solid rgb(var(--color-border));
            border-radius: 1.25rem;
            box-shadow: 0 30px 80px rgb(var(--color-primary)/.2), 0 2px 6px rgb(0 0 0/.05);
            overflow: hidden;
            animation: float 6s ease-in-out infinite
        }

        @keyframes float {
            50% {
                transform: translateY(-8px)
            }
        }

        .win-bar {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .7rem 1rem;
            border-bottom: 1px solid rgb(var(--color-border));
            background: rgb(var(--color-surface-2))
        }

        .win-bar i {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgb(var(--color-border))
        }

        .win-bar i:nth-child(1) {
            background: #ff5f57
        }

        .win-bar i:nth-child(2) {
            background: #febc2e
        }

        .win-bar i:nth-child(3) {
            background: #28c840
        }

        .caret::after {
            content: "";
            display: inline-block;
            width: 2px;
            height: 1em;
            background: rgb(var(--color-primary));
            margin-left: 2px;
            vertical-align: -2px;
            animation: blink 1s steps(2) infinite
        }

        @keyframes blink {
            50% {
                opacity: 0
            }
        }

        .chip {
            padding: .35rem .9rem;
            border-radius: 999px;
            border: 1px solid rgb(var(--color-border));
            font-size: .8rem;
            font-weight: 600;
            transition: all .25s
        }

        .chip.on {
            background: rgb(var(--color-primary));
            border-color: rgb(var(--color-primary));
            color: #fff;
            transform: scale(1.06)
        }

        .star {
            color: rgb(var(--color-border));
            font-size: 1.3rem;
            transition: color .2s, transform .25s
        }

        .star.on {
            color: rgb(var(--color-warning));
            transform: scale(1.2)
        }

        .bar {
            height: 6px;
            border-radius: 99px;
            background: rgb(var(--color-surface-2));
            overflow: hidden
        }

        .bar span {
            display: block;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgb(var(--color-primary)), rgb(var(--color-primary-light)));
            transition: width .5s ease
        }

        .done {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            text-align: center;
            background: rgb(var(--color-surface)/.96);
            opacity: 0;
            pointer-events: none;
            transition: opacity .35s
        }

        .done.show {
            opacity: 1
        }

        .done .ck {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgb(var(--color-success));
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 1.6rem;
            transform: scale(0);
            transition: transform .45s cubic-bezier(.2, 1.6, .4, 1) .1s
        }

        .done.show .ck {
            transform: scale(1)
        }

        /* cards */
        .lp-card {
            background: rgb(var(--color-surface));
            border: 1px solid rgb(var(--color-border));
            border-radius: 1.25rem
        }

        .lp-hover {
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease
        }

        .lp-hover:hover {
            transform: translateY(-5px);
            border-color: rgb(var(--color-primary));
            box-shadow: 0 20px 44px rgb(var(--color-primary)/.16)
        }

        .lp-icon {
            width: 3rem;
            height: 3rem;
            display: grid;
            place-items: center;
            border-radius: .9rem;
            background: rgb(var(--color-primary)/.12);
            color: rgb(var(--color-primary));
            font-size: 1.1rem;
            transition: transform .3s, background .3s, color .3s
        }

        .lp-hover:hover .lp-icon {
            background: rgb(var(--color-primary));
            color: #fff;
            transform: rotate(-8deg) scale(1.08)
        }

        .lp-link {
            color: rgb(var(--color-text-muted));
            font-weight: 500;
            transition: color .15s
        }

        .lp-link:hover {
            color: rgb(var(--color-primary))
        }

        .lp-input {
            width: 100%;
            padding: .8rem 1rem;
            border-radius: .75rem;
            border: 1px solid rgb(var(--color-border));
            background: rgb(var(--color-surface));
            color: rgb(var(--color-text));
            outline: none;
            transition: all .2s
        }

        .lp-input:focus {
            border-color: rgb(var(--color-primary));
            box-shadow: 0 0 0 4px rgb(var(--color-primary)/.14)
        }

        .nav-active {
            color: rgb(var(--color-primary)) !important
        }

        .step-line {
            position: absolute;
            left: 1.5rem;
            top: 3rem;
            bottom: -1rem;
            width: 2px;
            background: rgb(var(--color-border))
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid rgb(var(--color-primary-light));
            outline-offset: 2px
        }

        @media (prefers-reduced-motion:reduce) {

            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
                scroll-behavior: auto !important
            }

            .js .rv,
            .js .hin {
                opacity: 1;
                transform: none
            }
        }
    </style>
</head>

<body class="bg-theme-bg text-theme-text min-h-screen antialiased">
    <div id="progress"></div>

    {{-- NAVBAR --}}
    <header class="sticky top-0 z-40 border-b border-theme backdrop-blur-md"
        style="background:rgb(var(--color-surface)/.8)">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="#home" class="flex items-center gap-2 font-extrabold">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-theme-primary text-white"><i
                        class="fas fa-notes-medical"></i></span>
                <span class="text-lg tracking-tight">Doctor Forms</span>
            </a>
            <nav class="hidden items-center gap-8 md:flex" id="nav">
                <a href="#home" class="lp-link">Home</a>
                <a href="#forms" class="lp-link">Forms</a>
                <a href="#about" class="lp-link">About</a>
                <a href="#contact" class="lp-link">Contact</a>
            </nav>
            <div class="flex items-center gap-2">
                <button type="button" aria-label="Toggle theme"
                    @click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light')"
                    class="grid h-10 w-10 place-items-center rounded-full border border-theme bg-theme-surface">
                    <i class="fas" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
                </button>
                <button type="button"
                    class="grid h-10 w-10 place-items-center rounded-full border border-theme md:hidden"
                    aria-label="Menu" @click="menu = !menu">
                    <i class="fas" :class="menu ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>
        </div>
        <div x-show="menu" x-cloak x-transition @click.outside="menu = false"
            class="border-t border-theme px-4 py-3 md:hidden">
            <div class="flex flex-col gap-3">
                <a href="#home" @click="menu=false" class="lp-link">Home</a>
                <a href="#forms" @click="menu=false" class="lp-link">Forms</a>
                <a href="#about" @click="menu=false" class="lp-link">About</a>
                <a href="#contact" @click="menu=false" class="lp-link">Contact</a>
            </div>
        </div>
    </header>

    {{-- HERO --}}
    <section id="home" class="lp-hero">
        <div class="lp-grid"></div>
        <span class="blob a"></span><span class="blob b"></span><span class="blob c"></span>

        <div class="mx-auto max-w-6xl px-4 pb-16 pt-14 sm:px-6 md:pt-20">
            <div class="mx-auto max-w-3xl text-center">
                <a href="#forms" class="pill hin" style="--i:0"><b>New</b><span class="live"></span> Online patient
                    forms are open</a>
                <h1 class="h-display hin mt-6 text-4xl sm:text-6xl lg:text-7xl" style="--i:1">
                    Patient paperwork,<br><span class="grad-text">done in minutes.</span>
                </h1>
                <p class="hin mx-auto mt-6 max-w-xl text-lg leading-8 text-theme-muted" style="--i:2">
                    Pick a form, answer a few questions and send it straight to your doctor. No printing, no queues, no
                    account.
                </p>
                <div class="hin mt-8 flex flex-wrap justify-center gap-3" style="--i:3">
                    <a href="#forms" class="btn btn-primary btn-lg"
                        style="border-radius:999px;box-shadow:0 12px 28px rgb(var(--color-primary)/.35)">Fill a form <i
                            class="fas fa-arrow-down ml-1"></i></a>
                    <a href="#about" class="btn btn-secondary btn-lg" style="border-radius:999px">How it works</a>
                </div>
            </div>

            {{-- Live demo --}}
            <div class="hin relative mx-auto mt-14 max-w-xl" style="--i:4">
                <div class="win relative">
                    <div class="win-bar"><i></i><i></i><i></i><span
                            class="ml-3 text-xs font-semibold text-theme-muted">New patient registration</span></div>
                    <div class="relative space-y-5 p-6">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-xs font-semibold text-theme-muted">
                                <span>Progress</span><span id="pct">0%</span></div>
                            <div class="bar"><span id="fill"></span></div>
                        </div>
                        <div>
                            <p class="mb-1.5 text-sm font-bold">Full name</p>
                            <div
                                class="flex h-11 items-center rounded-xl border border-theme bg-theme-surface-2 px-3 text-sm">
                                <span id="typed" class="caret"></span></div>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-bold">Visit type</p>
                            <div class="flex flex-wrap gap-2" id="chips">
                                <span class="chip">First visit</span><span class="chip">Follow-up</span><span
                                    class="chip">Report review</span>
                            </div>
                        </div>
                        <div>
                            <p class="mb-1 text-sm font-bold">How was your experience?</p>
                            <div class="flex gap-1" id="stars"><span class="star">★</span><span
                                    class="star">★</span><span class="star">★</span><span
                                    class="star">★</span><span class="star">★</span></div>
                        </div>
                        <div class="btn btn-primary w-full" style="border-radius:.75rem;min-height:2.9rem">Submit form
                        </div>
                        <div class="done" id="done">
                            <div>
                                <div class="ck mx-auto"><i class="fas fa-check"></i></div>
                                <p class="mt-4 text-lg font-extrabold">Sent to your doctor</p>
                                <p class="text-sm text-theme-muted">Your response is safe and private.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mx-auto mt-16 grid max-w-3xl grid-cols-3 gap-4 text-center">
                <div class="rv" style="--d:0">
                    <p class="h-sec text-3xl sm:text-4xl"><span data-count="3">0</span> min</p>
                    <p class="mt-1 text-xs text-theme-muted sm:text-sm">Average time to fill</p>
                </div>
                <div class="rv" style="--d:1">
                    <p class="h-sec text-3xl sm:text-4xl"><span data-count="0">0</span> paper</p>
                    <p class="mt-1 text-xs text-theme-muted sm:text-sm">Fully paperless</p>
                </div>
                <div class="rv" style="--d:2">
                    <p class="h-sec text-3xl sm:text-4xl"><span data-count="24">0</span>/7</p>
                    <p class="mt-1 text-xs text-theme-muted sm:text-sm">Open any time</p>
                </div>
            </div>
        </div>
    </section>

    {{-- FORMS --}}
    <section id="forms" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
        <div class="max-w-2xl rv">
            <h2 class="h-sec text-3xl sm:text-4xl">Choose a form to fill</h2>
            <p class="mt-3 text-theme-muted">All published forms are listed below. Pick one and complete it online.</p>
        </div>

        @if ($forms->isEmpty())
            <div class="lp-card rv mt-10 border-dashed p-12 text-center">
                <span class="lp-icon mx-auto"><i class="fas fa-file-circle-question"></i></span>
                <p class="mt-4 text-lg font-bold">No published forms yet</p>
                <p class="mt-1 text-theme-muted">New forms will appear here as soon as they are published.</p>
            </div>
        @else
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($forms as $form)
                    <article class="lp-card lp-hover rv flex flex-col justify-between p-6"
                        style="--d:{{ $loop->index % 3 }}">
                        <div>
                            <span class="lp-icon"><i class="fas fa-file-medical"></i></span>
                            <h3 class="mt-4 text-xl font-extrabold tracking-tight">{{ $form->title }}</h3>
                            @if ($form->description)
                                <p class="mt-2 text-sm leading-6 text-theme-muted">
                                    {{ Str::limit($form->description, 120) }}</p>
                            @endif
                        </div>
                        <div class="mt-6 flex items-center justify-between border-t border-theme pt-4">
                            <span class="text-sm text-theme-muted"><i
                                    class="far fa-clipboard mr-1"></i>{{ $form->fields_count ?? $form->fields()->count() }}
                                questions</span>
                            <a href="{{ route('forms.public.show', $form) }}" class="btn btn-primary btn-sm"
                                style="border-radius:999px">Fill form</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- HOW IT WORKS --}}
    <section class="border-y border-theme bg-theme-surface">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="h-sec rv text-3xl sm:text-4xl">How it works</h2>
            <div class="mt-10 grid gap-10 md:grid-cols-3">
                @foreach ([['fa-hand-pointer', 'Pick a form', 'Open the form your clinic asked you to complete.'], ['fa-pen-to-square', 'Answer the questions', 'Type, choose, rate or upload reports. Long forms are split into short steps.'], ['fa-paper-plane', 'Submit securely', 'Your response reaches the care team instantly.']] as $i => $s)
                    <div class="rv relative" style="--d:{{ $i }}">
                        <span class="lp-icon" style="width:3rem;height:3rem"><i
                                class="fas {{ $s[0] }}"></i></span>
                        <h3 class="mt-4 text-lg font-extrabold">{{ $i + 1 }}. {{ $s[1] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-theme-muted">{{ $s[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ABOUT --}}
    <section id="about" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div class="rv">
                <h2 class="h-sec text-3xl sm:text-4xl">Built for doctors, simple for patients</h2>
                <p class="mt-4 leading-8 text-theme-muted">Doctor Forms helps clinics and hospitals collect patient
                    information without paper. Doctors build forms once and share them with a link. Patients fill them
                    from any device in a few minutes.</p>
                <p class="mt-4 leading-8 text-theme-muted">Smart questions appear only when they are needed, so every
                    patient sees a short, relevant form.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['fa-bolt', 'Fast to fill', 'Clean layout that works on phones.'], ['fa-shield-halved', 'Private', 'Responses are visible only to the care team.'], ['fa-code-branch', 'Smart questions', 'Fields show or hide based on answers.'], ['fa-paperclip', 'File uploads', 'Attach reports, prescriptions or images.']] as $i => $f)
                    <div class="lp-card lp-hover rv p-5" style="--d:{{ $i }}">
                        <span class="lp-icon"><i class="fas {{ $f[0] }}"></i></span>
                        <h3 class="mt-3 font-extrabold">{{ $f[1] }}</h3>
                        <p class="mt-1 text-sm text-theme-muted">{{ $f[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CONTACT --}}
    <section id="contact" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 md:pb-24">
        <div class="grid gap-10 lg:grid-cols-2">
            <div class="rv">
                <h2 class="h-sec text-3xl sm:text-4xl">Contact us</h2>
                <p class="mt-3 text-theme-muted">Questions about a form or need help? Send a message and we will reply
                    soon.</p>
                <ul class="mt-8 space-y-5">
                    <li class="flex items-center gap-4"><span class="lp-icon"><i
                                class="fas fa-envelope"></i></span><span>support@yourdomain.com</span></li>
                    <li class="flex items-center gap-4"><span class="lp-icon"><i
                                class="fas fa-phone"></i></span><span>+91 98765 43210</span></li>
                    <li class="flex items-center gap-4"><span class="lp-icon"><i
                                class="fas fa-location-dot"></i></span><span>Pune, Maharashtra, India</span></li>
                    <li class="flex items-center gap-4"><span class="lp-icon"><i
                                class="fas fa-clock"></i></span><span>Mon – Sat, 9:00 AM – 6:00 PM</span></li>
                </ul>
            </div>
            <div class="lp-card rv p-6 sm:p-8" style="--d:1">
                @if (session('contact_success'))
                    <div class="badge badge-success mb-4 rounded-xl p-3 text-sm font-medium"
                        style="white-space:normal">{{ session('contact_success') }}</div>
                @endif
                <form method="POST" action="{{ Route::has('contact.send') ? route('contact.send') : '#' }}"
                    class="space-y-4">
                    @csrf
                    <div><label for="c_name" class="mb-1 block text-sm font-semibold">Name</label><input
                            id="c_name" name="name" type="text" class="lp-input" placeholder="Your name"
                            required></div>
                    <div><label for="c_email" class="mb-1 block text-sm font-semibold">Email</label><input
                            id="c_email" name="email" type="email" class="lp-input"
                            placeholder="you@example.com" required></div>
                    <div><label for="c_msg" class="mb-1 block text-sm font-semibold">Message</label>
                        <textarea id="c_msg" name="message" rows="4" class="lp-input" placeholder="How can we help?" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-full" style="border-radius:.75rem">Send
                        message</button>
                </form>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="border-t border-theme bg-theme-surface">
        <div
            class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-theme-muted sm:px-6 md:flex-row">
            <span class="flex items-center gap-2 font-bold text-theme-text"><i
                    class="fas fa-notes-medical text-theme-primary"></i> Doctor Forms</span>
            <div class="flex gap-6">
                <a href="#home" class="lp-link">Home</a><a href="#forms" class="lp-link">Forms</a><a
                    href="#about" class="lp-link">About</a><a href="#contact" class="lp-link">Contact</a>
            </div>
            <span>&copy; {{ date('Y') }} Doctor Forms. All rights reserved.</span>
        </div>
    </footer>

    <script>
        (() => {
            const $ = s => document.querySelector(s),
                $$ = s => [...document.querySelectorAll(s)];
            const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

            // scroll progress bar
            const bar = $('#progress');
            const onScroll = () => {
                const h = document.documentElement;
                bar.style.transform = `scaleX(${h.scrollTop / Math.max(1, h.scrollHeight - h.clientHeight)})`;
            };
            addEventListener('scroll', onScroll, {
                passive: true
            });
            onScroll();

            // scroll reveal + count-up
            const count = el => {
                const to = +el.dataset.count;
                if (reduce || !to) {
                    el.textContent = to;
                    return;
                }
                const t0 = performance.now();
                const tick = t => {
                    const p = Math.min(1, (t - t0) / 1200);
                    el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
            };
            const io = new IntersectionObserver(es => es.forEach(e => {
                if (!e.isIntersecting) return;
                e.target.classList.add('in');
                e.target.querySelectorAll('[data-count]').forEach(count);
                io.unobserve(e.target);
            }), {
                threshold: .15
            });
            $$('.rv').forEach(el => io.observe(el));

            // active nav link
            const links = $$('#nav a');
            const so = new IntersectionObserver(es => es.forEach(e => {
                if (e.isIntersecting) links.forEach(a => a.classList.toggle('nav-active', a.hash === '#' + e
                    .target.id));
            }), {
                rootMargin: '-45% 0px -50% 0px'
            });
            ['home', 'forms', 'about', 'contact'].forEach(id => so.observe(document.getElementById(id)));

            // hero demo loop
            const typed = $('#typed'),
                chips = $$('#chips .chip'),
                stars = $$('#stars .star'),
                fill = $('#fill'),
                pct = $('#pct'),
                done = $('#done');
            const sleep = ms => new Promise(r => setTimeout(r, ms));
            const prog = n => {
                fill.style.width = n + '%';
                pct.textContent = n + '%';
            };
            async function demo() {
                if (reduce) {
                    typed.textContent = 'Priya Sharma';
                    chips[0].classList.add('on');
                    stars.forEach(s => s.classList.add('on'));
                    prog(100);
                    return;
                }
                while (true) {
                    typed.textContent = '';
                    chips.forEach(c => c.classList.remove('on'));
                    stars.forEach(s => s.classList.remove('on'));
                    done.classList.remove('show');
                    prog(0);
                    await sleep(900);
                    for (const ch of 'Priya Sharma') {
                        typed.textContent += ch;
                        await sleep(90);
                    }
                    prog(35);
                    await sleep(600);
                    chips[0].classList.add('on');
                    prog(60);
                    await sleep(900);
                    for (const s of stars.slice(0, 4)) {
                        s.classList.add('on');
                        await sleep(180);
                    }
                    prog(100);
                    await sleep(900);
                    done.classList.add('show');
                    await sleep(2600);
                }
            }
            demo();
        })();
    </script>
</body>

</html>
