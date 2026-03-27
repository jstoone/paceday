<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen paceday-gradient">
        <div class="mx-auto max-w-lg px-5 pb-16 pt-12">

            {{-- Logo --}}
            <div class="flex items-center gap-2.5">
                <x-app-logo-icon class="size-7 text-rust" />
                <span class="font-heading text-xl font-bold tracking-tight text-bark">Paceday</span>
            </div>

            {{-- Tagline --}}
            <div class="mt-8">
                <h1 class="font-heading text-3xl font-bold text-bark leading-snug">
                    Know your patterns.<br>
                    Shop smarter.
                </h1>
                <p class="mt-3 text-bark-light">
                    Track how long things last and how often you do things. One question at a time.
                </p>
            </div>

            {{-- Interactive hero --}}
            <div
                class="mt-8"
                x-data="{
                    tab: 'how-long',
                    durationIndex: 0,
                    frequencyIndex: 0,
                    durationExamples: [
                        { amount: '40', unit: 'capsules', thing: 'coffee' },
                        { amount: '6', unit: 'rolls', thing: 'toilet paper' },
                        { amount: '1', unit: 'tube', thing: 'toothpaste' },
                        { amount: '30', unit: 'bags', thing: 'trash bags' },
                    ],
                    frequencyExamples: [
                        { thing: 'exercise', period: 'week' },
                        { thing: 'water my plants', period: 'month' },
                        { thing: 'do laundry', period: 'week' },
                        { thing: 'clean the bathroom', period: 'month' },
                    ],
                    fading: false,
                    timer: null,
                    resize(el) {
                        const len = Math.max(el.value.length, el.placeholder.length, 2);
                        el.style.width = (el.type === 'number' ? len + 1.5 : len + 1) + 'ch';
                    },
                    get duration() { return this.durationExamples[this.durationIndex]; },
                    get frequency() { return this.frequencyExamples[this.frequencyIndex]; },
                    startTimer() {
                        this.stopTimer();
                        this.timer = setInterval(() => {
                            if (this.tab === 'how-good') return;
                            this.fading = true;
                            setTimeout(() => {
                                if (this.tab === 'how-long') {
                                    this.durationIndex = (this.durationIndex + 1) % this.durationExamples.length;
                                } else {
                                    this.frequencyIndex = (this.frequencyIndex + 1) % this.frequencyExamples.length;
                                }
                                this.fading = false;
                            }, 300);
                        }, 3500);
                    },
                    stopTimer() {
                        if (this.timer) { clearInterval(this.timer); this.timer = null; }
                    },
                    switchTab(t) {
                        this.tab = t;
                        this.startTimer();
                    },
                    init() { this.startTimer(); },
                }"
                x-on:visibilitychange.window="document.hidden ? stopTimer() : startTimer()"
            >
                {{-- Tab switcher --}}
                <div class="flex gap-2">
                    <button
                        type="button"
                        x-on:click="switchTab('how-long')"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="tab === 'how-long' ? 'bg-rust text-white shadow-md shadow-rust/25' : 'bg-sand text-bark-light hover:bg-zinc-200'"
                    >
                        How long
                    </button>
                    <button
                        type="button"
                        x-on:click="switchTab('how-many')"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="tab === 'how-many' ? 'bg-rust text-white shadow-md shadow-rust/25' : 'bg-sand text-bark-light hover:bg-zinc-200'"
                    >
                        How many
                    </button>
                    <button
                        type="button"
                        x-on:click="switchTab('how-good')"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="tab === 'how-good' ? 'bg-rust text-white shadow-md shadow-rust/25' : 'bg-sand text-bark-light hover:bg-zinc-200'"
                    >
                        How good
                    </button>
                </div>

                {{-- Sentence builder cards --}}
                <div class="mt-4">
                    {{-- How long --}}
                    <div x-show="tab === 'how-long'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <div class="paceday-card">
                            <p class="sentence-builder leading-relaxed" :class="fading && 'opacity-0'" style="transition: opacity 0.3s ease">
                                <span>How long does</span>
                                <input
                                    type="number"
                                    min="1"
                                    class="sentence-input"
                                    :placeholder="duration.amount"
                                    x-init="resize($el)"
                                    x-effect="resize($el)"
                                    @input="resize($el)"
                                />
                                <input
                                    type="text"
                                    class="sentence-input"
                                    :placeholder="duration.unit"
                                    x-init="resize($el)"
                                    x-effect="resize($el)"
                                    @input="resize($el)"
                                />
                                <span>of</span>
                                <input
                                    type="text"
                                    class="sentence-input"
                                    :placeholder="duration.thing"
                                    x-init="resize($el)"
                                    x-effect="resize($el)"
                                    @input="resize($el)"
                                />
                                <span>last?</span>
                            </p>
                        </div>
                    </div>

                    {{-- How many --}}
                    <div x-show="tab === 'how-many'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <div class="paceday-card">
                            <p class="sentence-builder leading-relaxed" :class="fading && 'opacity-0'" style="transition: opacity 0.3s ease">
                                <span>How many times do I</span>
                                <input
                                    type="text"
                                    class="sentence-input"
                                    :placeholder="frequency.thing"
                                    x-init="resize($el)"
                                    x-effect="resize($el)"
                                    @input="resize($el)"
                                />
                                <span>per</span>
                                <input
                                    type="text"
                                    class="sentence-input !w-auto"
                                    :placeholder="frequency.period"
                                    x-init="resize($el)"
                                    x-effect="resize($el)"
                                    @input="resize($el)"
                                />
                                <span>?</span>
                            </p>
                        </div>
                    </div>

                    {{-- How good --}}
                    <div x-show="tab === 'how-good'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <div class="paceday-card space-y-4">
                            <p class="font-heading text-xl font-semibold text-bark">
                                Hey, real quick &mdash;
                            </p>
                            <p class="text-bark-light leading-relaxed">
                                In a world full of trackers that want you to optimise everything, I just wanted to pause and ask: are you doing okay?
                            </p>
                            <p class="text-bark-light leading-relaxed">
                                Not your habits. Not your output. <span class="font-medium text-bark">You.</span>
                            </p>
                            <p class="text-bark-light leading-relaxed">
                                This app is about knowing your patterns so you can stress less. But the most important pattern is the one where you check in with yourself.
                            </p>
                            <p class="font-heading text-sm font-medium text-bark-light">
                                xoxo Jakob Steinn
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mock question card --}}
            <div class="mt-10">
                <p class="mb-3 font-heading text-sm font-medium text-bark-light uppercase tracking-wider">What tracking looks like</p>

                <div class="paceday-card space-y-4">
                    {{-- Question label --}}
                    <h2 class="font-heading text-lg font-bold text-bark">
                        How long does 40 capsules of coffee last?
                    </h2>

                    {{-- Hero counter + status badge --}}
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                            <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                            Round in progress
                        </span>
                        <span class="text-xs text-bark-light">Started 12 days ago</span>
                    </div>

                    <div class="py-4 text-center">
                        <span class="text-6xl font-bold text-rust" style="font-family: var(--font-heading)">12</span>
                        <p class="mt-1 text-sm font-medium text-bark-light">days</p>
                    </div>

                    {{-- Guess chip --}}
                    <div class="text-center">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-sand px-3 py-1 text-sm text-bark-light">
                            Guess: <span class="font-medium text-bark">3 weeks</span>
                        </span>
                    </div>

                    {{-- Mini timeline --}}
                    <div class="space-y-2 border-t border-zinc-100 pt-4">
                        <p class="font-heading text-xs font-medium text-bark-light uppercase tracking-wider">Timeline</p>

                        {{-- Completed round --}}
                        <div class="rounded-2xl bg-zinc-50 px-4 py-2.5">
                            <div class="flex items-baseline justify-between">
                                <span class="text-sm font-medium text-bark">Feb 8 &mdash; Feb 27</span>
                                <span class="text-sm font-medium text-bark">19 days</span>
                            </div>
                            <p class="mt-0.5 text-xs text-bark-light">
                                Guessed 3 weeks <span class="font-medium text-amber-600">&mdash; ran out 2 days early</span>
                            </p>
                        </div>

                        {{-- Note --}}
                        <div class="rounded-2xl bg-zinc-50 px-4 py-2.5">
                            <p class="text-sm text-bark-light italic">
                                &ldquo;Switched to stronger capsules&rdquo;
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-400">Feb 15</p>
                        </div>

                        {{-- Guess update --}}
                        <div class="rounded-2xl bg-zinc-50 px-4 py-2.5">
                            <p class="text-sm text-bark-light">
                                Guess updated to <span class="font-medium text-bark">3 weeks</span>
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-400">Feb 8</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal footer --}}
            <div class="mt-12 text-center">
                <p class="text-bark-light leading-relaxed">
                    Built by <span class="font-medium text-bark">Jakob Steinn</span> &mdash; songwriter, toolmaker, someone who got tired of guessing when the coffee runs out.
                </p>

                <div class="mt-8">
                    <a
                        href="{{ route('register') }}"
                        class="inline-block rounded-full bg-rust px-8 py-3 font-heading text-base font-semibold text-white shadow-md shadow-rust/25 transition hover:bg-rust-dark hover:shadow-lg hover:shadow-rust/30 hover:-translate-y-0.5"
                    >
                        Start tracking something
                    </a>
                </div>

                <p class="mt-4 text-sm text-bark-light">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-rust transition hover:text-rust-dark">
                        Log in
                    </a>
                </p>
            </div>

        </div>

        @fluxScripts
    </body>
</html>
