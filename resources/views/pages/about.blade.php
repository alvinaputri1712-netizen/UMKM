@extends('layouts.app')

@section('title', $siteSettings['about.title'])
@section('meta_description', $siteSettings['about.description'])

@section('content')
    @php
        $profileParagraphs = collect([
            $siteSettings['home.about.paragraph_1'],
            $siteSettings['home.about.paragraph_2'],
        ])->filter(fn (string $paragraph): bool => filled($paragraph))->values();
        $heroDescription = filled($siteSettings['about.description'])
            ? $siteSettings['about.description']
            : $profileParagraphs->first();
        $schoolImage = $siteConfiguration->imageUrl($siteSettings['about.image']);
        $schoolName = $siteSettings['identity.school_name'] ?? 'SMK Islamiyah Ciputat';
        $schoolAddress = filled($siteSettings['identity.address'])
            ? $siteSettings['identity.address']
            : ($siteSettings['identity.location'] ?? 'Alamat sekolah belum tersedia');
        $mapQuery = filled($siteSettings['identity.address'])
            ? $siteSettings['identity.address']
            : ($siteSettings['identity.location'] ?? $schoolName);
        $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($mapQuery);
        $missions = collect(preg_split('/\r\n|\r|\n/', $siteSettings['about.mission'] ?? ''))
            ->filter(fn (string $mission): bool => trim($mission) !== '')
            ->values();
    @endphp

    <section class="section public-page about-page">
        <div class="container public-page__container about-page__container">
            <header class="about-hero">
                <div class="about-hero__copy">
                    <nav class="about-breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}">Beranda</a>
                        <span>/</span>
                        <span>Tentang Kami</span>
                    </nav>

                    <h1>Tentang Kami</h1>
                    <div class="about-hero__line" aria-hidden="true"></div>

                    @if (filled($heroDescription))
                        <p>{{ $heroDescription }}</p>
                    @endif
                </div>

                @if ($schoolImage)
                    <figure class="about-hero__image">
                        <img
                            src="{{ $schoolImage }}"
                            alt="{{ $siteSettings['home.about.image_alt'] ?? 'Bangunan sekolah SMK Islamiyah Ciputat' }}"
                            fetchpriority="high"
                        >
                    </figure>
                @endif
            </header>

            @if ($schoolImage || $profileParagraphs->isNotEmpty() || filled($schoolAddress))
                <section class="about-profile" id="about-profile" aria-labelledby="about-profile-title">
                    @if ($schoolImage)
                        <figure class="about-profile__image">
                            <img
                                src="{{ $schoolImage }}"
                                alt="{{ $siteSettings['home.about.image_alt'] ?? 'Bangunan sekolah SMK Islamiyah Ciputat' }}"
                                loading="lazy"
                            >
                        </figure>
                    @endif

                    <div class="about-profile__copy">
                        <div class="about-section-label">Profil Sekolah</div>
                        <h2 id="about-profile-title">{{ $schoolName }}</h2>

                        @foreach ($profileParagraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach

                        <div class="about-profile__meta">
                            @if (filled($schoolAddress))
                                <div class="about-profile__meta-item about-profile__meta-item--location">
                                    <span class="about-profile__meta-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.686-6-11a6 6 0 1 1 12 0c0 5.314-6 11-6 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    </span>
                                    <div>
                                        <strong>Alamat Sekolah</strong>
                                        <span>{{ $schoolAddress }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="about-profile__meta-item about-profile__meta-item--type">
                                <span class="about-profile__meta-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v3.75"/><path d="M4 10.5h16"/><path d="M6 14h12v6H6z"/></svg>
                                </span>
                                <div>
                                    <strong>Jenis Sekolah</strong>
                                    <span>Sekolah Menengah Kejuruan (SMK)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="about-principles" aria-label="Visi dan misi sekolah">
                <article class="about-principle about-principle--vision">
                    <div class="about-principle__heading">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3.5"/></svg>
                        <h2>Visi Sekolah</h2>
                    </div>
                    <div class="about-principle__line" aria-hidden="true"></div>

                    @if (filled($siteSettings['about.vision']))
                        <div class="about-principle__body">
                            <span class="about-principle__quote" aria-hidden="true">“</span>
                            <p>{{ $siteSettings['about.vision'] }}</p>
                        </div>
                    @else
                        <p class="about-principle__empty">Informasi visi sekolah belum tersedia.</p>
                    @endif
                </article>

                <article class="about-principle about-principle--mission">
                    <div class="about-principle__heading">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V6.5A2.5 2.5 0 0 1 6.5 4H20v15.5H6.5A2.5 2.5 0 0 0 4 19.5Z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg>
                        <h2>Misi Sekolah</h2>
                    </div>
                    <div class="about-principle__line" aria-hidden="true"></div>

                    @if ($missions->isNotEmpty())
                        <ul class="about-mission-list">
                            @foreach ($missions as $mission)
                                <li>
                                    <span class="about-mission-list__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                                    </span>
                                    <span>{{ trim($mission) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="about-principle__empty">Informasi misi sekolah belum tersedia.</p>
                    @endif
                </article>
            </section>

            <section class="about-location" aria-labelledby="about-location-title">
                <div class="about-location__info">
                    <div class="about-location__label-wrap">
                        <div class="about-location__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.686-6-11a6 6 0 1 1 12 0c0 5.314-6 11-6 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </div>
                        <h2 id="about-location-title">Lokasi Sekolah</h2>
                    </div>
                    <div class="about-principle__line about-principle__line--small" aria-hidden="true"></div>

                    <p>Temukan lokasi SMK Islamiyah Ciputat melalui peta di samping atau buka langsung di Google Maps.</p>

                    <div class="about-location__address-box">
                        <div class="about-location__address-name">{{ $schoolName }}</div>
                        <div>{{ $schoolAddress }}</div>
                    </div>

                    <a class="about-location__button" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-6-5.686-6-11a6 6 0 1 1 12 0c0 5.314-6 11-6 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        Buka di Google Maps
                    </a>
                </div>

                <div class="about-location__map-wrap">
                    <iframe
                        title="Peta lokasi {{ $schoolName }}"
                        src="https://www.google.com/maps?q={{ urlencode($mapQuery) }}&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
            </section>
        </div>
    </section>
@endsection
