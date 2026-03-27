<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen paceday-gradient">
        <div
            class="mx-auto max-w-lg px-5 pb-16 pt-12"
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
                resize(el, placeholder) {
                    const text = el.value || placeholder || el.placeholder || '';
                    const canvas = this._canvas || (this._canvas = document.createElement('canvas'));
                    const ctx = canvas.getContext('2d');
                    ctx.font = getComputedStyle(el).font;
                    el.style.width = Math.ceil(ctx.measureText(text).width + 4) + 'px';
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
                switching: false,
                switchTab(t) {
                    if (t === this.tab) return;
                    this.switching = true;
                    setTimeout(() => {
                        this.tab = t;
                        this.$nextTick(() => this.switching = false);
                    }, 150);
                    this.startTimer();
                },
                init() { this.startTimer(); },
            }"
            x-on:visibilitychange.window="document.hidden ? stopTimer() : startTimer()"
        >

            {{-- Logo --}}
            <div class="landing-entrance flex items-center gap-2.5">
                <x-app-logo-icon class="size-7 text-rust" />
                <span class="font-heading text-xl font-bold tracking-tight text-bark">Paceday</span>
            </div>

            {{-- Tagline --}}
            <div class="landing-entrance mt-10" style="animation-delay: 0.1s">
                <h1 class="font-heading text-3xl font-bold text-bark leading-snug">
                    Know your patterns.<br>
                    Shop smarter.
                </h1>
                <p class="mt-3 text-bark-light leading-relaxed">
                    Track how long things last and how often you do things.<br class="hidden sm:inline">
                    One question at a time.
                </p>
            </div>

            {{-- Interactive hero --}}
            <div class="landing-entrance mt-8" style="animation-delay: 0.2s">
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
                        How are you?
                    </button>
                </div>

                {{-- Sentence builder cards --}}
                <div class="mt-4 transition-opacity duration-150" :class="switching ? 'opacity-0' : 'opacity-100'">
                    {{-- How long --}}
                    <div x-show="tab === 'how-long'" x-cloak>
                        <div class="paceday-card">
                            <p class="sentence-builder leading-relaxed" :class="fading && 'opacity-0'" style="transition: opacity 0.3s ease">
                                <span>How long does</span>
                                <input
                                    type="number"
                                    min="1"
                                    class="sentence-input"
                                    :placeholder="duration.amount"
                                    x-init="resize($el, duration.amount)"
                                    x-effect="resize($el, duration.amount)"
                                    @input="resize($el, duration.amount)"
                                />
                                <input
                                    type="text"
                                    class="sentence-input"
                                    :placeholder="duration.unit"
                                    x-init="resize($el, duration.unit)"
                                    x-effect="resize($el, duration.unit)"
                                    @input="resize($el, duration.unit)"
                                />
                                <span>of</span>
                                <input
                                    type="text"
                                    class="sentence-input"
                                    :placeholder="duration.thing"
                                    x-init="resize($el, duration.thing)"
                                    x-effect="resize($el, duration.thing)"
                                    @input="resize($el, duration.thing)"
                                />
                                <span>last?</span>
                            </p>
                        </div>
                    </div>

                    {{-- How many --}}
                    <div x-show="tab === 'how-many'" x-cloak>
                        <div class="paceday-card">
                            <div :class="fading && 'opacity-0'" style="transition: opacity 0.3s ease">
                                <p class="sentence-builder leading-relaxed">
                                    <span>How many times</span>
                                </p>
                                <p class="sentence-builder leading-relaxed mt-1">
                                    <span>do I</span>
                                    <input
                                        type="text"
                                        class="sentence-input"
                                        :placeholder="frequency.thing"
                                        x-init="resize($el, frequency.thing)"
                                        x-effect="resize($el, frequency.thing)"
                                        @input="resize($el, frequency.thing)"
                                    />
                                    <span>per</span>
                                    <input
                                        type="text"
                                        class="sentence-input"
                                        :placeholder="frequency.period"
                                        x-init="resize($el, frequency.period)"
                                        x-effect="resize($el, frequency.period)"
                                        @input="resize($el, frequency.period)"
                                    />
                                    <span>?</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- How are you? --}}
                    <div x-show="tab === 'how-good'" x-cloak>
                        <div class="rounded-3xl bg-amber-50/60 p-6 shadow-lg shadow-zinc-900/[0.04] ring-1 ring-amber-200/40 space-y-4">
                            <p class="font-heading text-xl font-semibold text-bark">
                                Hey you!
                            </p>
                            <p class="text-bark-light leading-relaxed">
                                In a world full of trackers that want you to optimise everything, I just wanted to pause and ask: are you doing okay?
                            </p>
                            <p class="text-bark-light leading-relaxed">
                                Not your habits. Not your output. <span class="font-semibold text-bark">You.</span>
                            </p>
                            <p class="text-bark-light leading-relaxed">
                                No feeling is final. There&rsquo;s another minute from now, an hour thereafter and a day tomorrow. If you&rsquo;re feeling lonely, reach out. It truly helps.
                            </p>
                            <p class="text-bark-light leading-relaxed font-medium text-bark">
                                This too, soon shall pass.
                            </p>
                            <p class="font-heading text-sm font-medium text-bark pt-2">
                                xoxo Jakob Steinn
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Showcase section --}}
            <div class="landing-entrance mt-14 transition-opacity duration-150" style="animation-delay: 0.35s" :class="switching ? 'opacity-0' : 'opacity-100'">
                <p class="mb-3 font-heading text-sm font-medium text-bark-light" x-show="tab !== 'how-good'">What tracking looks like</p>

                {{-- How long: duration tracking example --}}
                <div x-show="tab === 'how-long'" x-cloak>
                    <div class="paceday-card space-y-4">
                        <h2 class="font-heading text-lg font-bold text-bark">
                            How long does 40 capsules of coffee last?
                        </h2>

                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                Round in progress
                            </span>
                            <span class="text-xs text-bark-light">Started 12 days ago</span>
                        </div>

                        <div class="py-5 text-center">
                            <span class="landing-hero-counter text-6xl font-bold text-rust" style="font-family: var(--font-heading)">12</span>
                            <p class="mt-1 text-sm font-medium text-bark-light">days</p>
                        </div>

                        <div class="text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sand px-3 py-1 text-sm text-bark-light">
                                Guess: <span class="font-medium text-bark">3 weeks</span>
                            </span>
                        </div>

                        <div class="border-t border-zinc-100 pt-4">
                            <p class="mb-3 font-heading text-xs font-medium text-bark-light">Timeline</p>

                            <div class="relative ml-3 space-y-0 border-l-2 border-zinc-100 pl-4">
                                <div class="relative pb-3">
                                    <span class="absolute -left-[calc(1rem+5px)] top-1 size-2 rounded-full bg-zinc-300"></span>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-sm font-medium text-bark">Feb 8 &mdash; Feb 27</span>
                                        <span class="text-sm font-medium text-bark">19 days</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-bark-light">
                                        Guessed 3 weeks <span class="font-medium text-amber-600">&mdash; ran out 2 days early</span>
                                    </p>
                                </div>

                                <div class="relative pb-3">
                                    <span class="absolute -left-[calc(1rem+5px)] top-1 size-2 rounded-full bg-zinc-200"></span>
                                    <p class="text-sm text-bark-light italic">
                                        &ldquo;Switched to stronger capsules&rdquo;
                                    </p>
                                    <p class="mt-0.5 text-xs text-zinc-400">Feb 15</p>
                                </div>

                                <div class="relative">
                                    <span class="absolute -left-[calc(1rem+5px)] top-1 size-2 rounded-full bg-zinc-200"></span>
                                    <p class="text-sm text-bark-light">
                                        Guess updated to <span class="font-medium text-bark">3 weeks</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-zinc-400">Feb 8</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- How many: frequency tracking example --}}
                <div x-show="tab === 'how-many'" x-cloak>
                    <div class="paceday-card space-y-4">
                        <h2 class="font-heading text-lg font-bold text-bark">
                            How many times do I exercise per week?
                        </h2>

                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                This week
                            </span>
                            <span class="text-xs text-bark-light">3 days left</span>
                        </div>

                        <div class="py-5 text-center">
                            <span class="landing-hero-counter text-6xl font-bold text-rust" style="font-family: var(--font-heading)">4</span>
                            <p class="mt-1 text-sm font-medium text-bark-light">times this week</p>
                        </div>

                        <div class="text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sand px-3 py-1 text-sm text-bark-light">
                                Guess: <span class="font-medium text-bark">3 times</span>
                            </span>
                        </div>

                        <div class="border-t border-zinc-100 pt-4">
                            <p class="mb-3 font-heading text-xs font-medium text-bark-light">Timeline</p>

                            <div class="relative ml-3 space-y-0 border-l-2 border-zinc-100 pl-4">
                                <div class="relative pb-3">
                                    <span class="absolute -left-[calc(1rem+5px)] top-1 size-2 rounded-full bg-zinc-300"></span>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-sm font-medium text-bark">Last week</span>
                                        <span class="text-sm font-medium text-bark">5 times</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-bark-light">
                                        Guessed 3 <span class="font-medium text-green-600">&mdash; beat your guess!</span>
                                    </p>
                                </div>

                                <div class="relative pb-3">
                                    <span class="absolute -left-[calc(1rem+5px)] top-1 size-2 rounded-full bg-zinc-200"></span>
                                    <p class="text-sm text-bark-light italic">
                                        &ldquo;Morning runs feel easier now&rdquo;
                                    </p>
                                    <p class="mt-0.5 text-xs text-zinc-400">Mar 20</p>
                                </div>

                                <div class="relative">
                                    <span class="absolute -left-[calc(1rem+5px)] top-1 size-2 rounded-full bg-zinc-300"></span>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-sm font-medium text-bark">Two weeks ago</span>
                                        <span class="text-sm font-medium text-bark">2 times</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-bark-light">
                                        Guessed 3 <span class="font-medium text-amber-600">&mdash; 1 short</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- How are you: photo of Jakob --}}
                <div x-show="tab === 'how-good'" x-cloak>
                    <div class="overflow-hidden rounded-3xl shadow-lg shadow-zinc-900/[0.04]">
                        <img
                            src="/selfie.jpeg"
                            alt="Jakob Steinn"
                            class="w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>

            {{-- Personal footer --}}
            <div class="landing-entrance mt-16 text-center transition-opacity duration-150" style="animation-delay: 0.5s" :class="switching ? 'opacity-0' : 'opacity-100'">
                <p class="text-bark-light leading-relaxed">
                    Built by <span class="font-semibold text-bark">Jakob Steinn</span> &mdash; songwriter, toolmaker,<br class="hidden sm:inline">
                    someone who got tired of guessing when the coffee runs out.
                </p>

                <div class="mt-8" x-show="tab !== 'how-good'" x-cloak>
                    <a
                        href="{{ route('register') }}"
                        class="inline-block rounded-full bg-rust px-8 py-3.5 font-heading text-base font-semibold text-white shadow-lg shadow-rust/25 transition-all duration-200 hover:bg-rust-dark hover:shadow-xl hover:shadow-rust/30 hover:-translate-y-0.5 active:translate-y-0"
                    >
                        Start tracking something
                    </a>
                </div>

                <p class="mt-8 font-heading text-base font-medium text-bark-light" x-show="tab === 'how-good'" x-cloak>
                    Thank you for visiting, means the world.
                </p>

                <p class="mt-5 text-sm text-bark-light" x-show="tab !== 'how-good'" x-cloak>
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
