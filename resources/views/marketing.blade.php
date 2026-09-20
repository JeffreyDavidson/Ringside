<x-layouts.marketing>
    <header id="top" class="site-header">
        <div class="page-width flex items-center justify-between gap-6">
            <a class="wordmark" href="{{ route('home') }}" aria-label="{{ __('marketing.home') }}"
                >RING<span>SIDE</span></a>
            <nav class="flex items-center gap-5 md:gap-10" aria-label="{{ __('marketing.navigation') }}">
                <a class="nav-link" href="#roster">{{ __('marketing.features') }}</a>
                <a class="nav-link desktop-link" href="#how-it-works">{{ __('marketing.workflow') }}</a>
                @auth
                    <a class="button button-outline header-action" href="{{ route('dashboard') }}">
                        {{ __('marketing.dashboard') }} <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    </a>
                @else
                    <a class="button button-outline header-action" href="{{ route('login') }}">
                        {{ __('marketing.sign_in') }} <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="main">
        <section class="hero" aria-labelledby="hero-title">
            <img
                class="hero-image"
                src="{{ asset('images/marketing/arena.webp') }}"
                srcset="{{ asset('images/marketing/arena-960.webp') }} 960w, {{ asset('images/marketing/arena.webp') }} 1672w"
                sizes="100vw"
                width="1672"
                height="941"
                alt=""
                fetchpriority="high"
            />
            <div class="hero-scrim" aria-hidden="true"></div>
            <div class="page-width hero-content">
                <p class="hero-badge">{{ __('marketing.hero_badge') }}</p>
                <h1 id="hero-title" class="display hero-title">
                    <span>{{ __('marketing.hero.first') }}</span>
                    <span class="text-signal">{{ __('marketing.hero.second') }}</span>
                </h1>
                <p class="hero-description">{{ __('marketing.hero.description') }}</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('register') }}">
                        {{ __('marketing.create_account') }} <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    </a>
                    <a class="button button-outline" href="#capabilities">
                        {{ __('marketing.explore') }} <x-heroicon-o-arrow-down aria-hidden="true" />
                    </a>
                </div>
                <p class="hero-note">{{ __('marketing.hero.note') }}</p>
            </div>
        </section>

        <nav class="feature-index" aria-label="{{ __('marketing.index_label') }}">
            <div class="page-width grid grid-cols-2 md:grid-cols-4">
                @foreach (__('marketing.index') as $target => $label)
                    <a href="#{{ $target }}">
                        <span>{{ $label }}</span>
                        <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    </a>
                @endforeach
            </div>
        </nav>

        <section id="roster" class="section-pad page-width" aria-labelledby="roster-title">
            <div class="section-heading">
                <h2 id="roster-title" class="display">{{ __('marketing.roster.title') }}</h2>
                <p>{{ __('marketing.roster.description') }}</p>
            </div>
            <div class="roster-list">
                @foreach (__('marketing.roster.items') as $item)
                    <div class="roster-row">
                        <h3 class="display">{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="how-it-works" class="show-section section-pad" aria-labelledby="show-title">
            <div class="page-width">
                <div class="section-heading">
                    <h2 id="show-title" class="display">{{ __('marketing.show.title') }}</h2>
                    <p>{{ __('marketing.show.description') }}</p>
                </div>
                <div class="show-steps grid gap-12 md:grid-cols-2 md:gap-20">
                    <div id="events">
                        <x-heroicon-o-calendar-days class="step-icon" aria-hidden="true" />
                        <h3 class="display">{{ __('marketing.show.events_title') }}</h3>
                        <p>{{ __('marketing.show.events_description') }}</p>
                        <p class="step-detail">{{ __('marketing.show.events_detail') }}</p>
                    </div>
                    <div id="matches">
                        <x-heroicon-o-clipboard-document-list class="step-icon" aria-hidden="true" />
                        <h3 class="display">{{ __('marketing.show.matches_title') }}</h3>
                        <p>{{ __('marketing.show.matches_description') }}</p>
                        <p class="step-detail">{{ __('marketing.show.matches_detail') }}</p>
                    </div>
                </div>
                <div class="workflow-steps">
                    @foreach (__('marketing.steps') as $step)
                        <div class="workflow-step">
                            <span class="workflow-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="display">{{ $step['title'] }}</h3>
                            <p>{{ $step['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="capabilities" class="capabilities-section section-pad" aria-labelledby="capabilities-title">
            <div class="page-width">
                <div class="section-heading">
                    <h2 id="capabilities-title" class="display">{{ __('marketing.capabilities.title') }}</h2>
                    <p>{{ __('marketing.capabilities.description') }}</p>
                </div>
                <div class="capabilities-grid">
                    @foreach (__('marketing.capabilities.items') as $item)
                        <article class="capability-item">
                            <h3 class="display">{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section
            id="championships"
            class="championship-section page-width section-pad"
            aria-labelledby="championship-title"
        >
            <div class="championship-photo">
                <img
                    src="{{ asset('images/marketing/championship.webp') }}"
                    width="1448"
                    height="1086"
                    alt="{{ __('marketing.championships.image_alt') }}"
                    loading="lazy"
                    decoding="async"
                />
            </div>
            <div>
                <h2 id="championship-title" class="display">{{ __('marketing.championships.title') }}</h2>
                <p class="section-copy">{{ __('marketing.championships.description') }}</p>
                <ul class="title-features">
                    @foreach (__('marketing.championships.items') as $item)
                        <li><x-heroicon-o-check aria-hidden="true" /> {{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="faq-section section-pad" aria-labelledby="faq-title">
            <div class="page-width faq-layout">
                <div>
                    <h2 id="faq-title" class="display">{{ __('marketing.faq.title') }}</h2>
                    <p class="section-copy">{{ __('marketing.faq.intro') }}</p>
                </div>
                <div class="faq-list">
                    @foreach (__('marketing.faq.items') as $item)
                        <details>
                            <summary>{{ $item['question'] }} <x-heroicon-o-plus aria-hidden="true" /></summary>
                            <p>{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="closing-section section-pad page-width" aria-labelledby="closing-title">
            <h2 id="closing-title" class="display">{{ __('marketing.closing.title') }}</h2>
            <div>
                <p>{{ __('marketing.closing.description') }}</p>
                @auth
                    <a class="button button-primary" href="{{ route('dashboard') }}">
                        {{ __('marketing.dashboard') }} <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    </a>
                @else
                    <a class="button button-primary" href="{{ route('register') }}">
                        {{ __('marketing.create_account') }} <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    </a>
                @endauth
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="page-width flex flex-wrap items-center justify-between gap-6">
            <div class="flex flex-wrap items-center gap-x-7 gap-y-3">
                <a class="wordmark" href="{{ route('home') }}" aria-label="{{ __('marketing.home') }}"
                    >RING<span>SIDE</span></a>
                <p>© {{ now()->year }} Ringside. {{ __('marketing.footer') }}</p>
            </div>
            <a class="footer-link" href="#top"
                >{{ __('marketing.back_to_top') }} <x-heroicon-o-arrow-up aria-hidden="true"
            /></a>
        </div>
    </footer>
</x-layouts.marketing>
