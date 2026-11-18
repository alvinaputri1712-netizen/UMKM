@extends('layouts.app')

@section('title', $siteSettings['about.title'])
@section('meta_description', $siteSettings['about.description'])

@section('content')
    @php
        $profileParagraphs = collect([
            filled($siteSettings['about.profile_paragraph_1'] ?? '')
                ? $siteSettings['about.profile_paragraph_1']
                : $siteSettings['home.about.paragraph_1'],
            filled($siteSettings['about.profile_paragraph_2'] ?? '')
                ? $siteSettings['about.profile_paragraph_2']
                : $siteSettings['home.about.paragraph_2'],
        ])->filter(fn (string $paragraph): bool => filled($paragraph))->values();
        $schoolImage = $siteConfiguration->imageUrl($siteSettings['about.image']);
        $schoolName = $siteSettings['identity.school_name'] ?? 'SMK Islamiyah Ciputat';
        $profileHeading = filled($siteSettings['about.profile_heading'] ?? '')
            ? $siteSettings['about.profile_heading']
            : $schoolName;
        $profileImageAlt = filled($siteSettings['about.profile_image_alt'] ?? '')
            ? $siteSettings['about.profile_image_alt']
            : ($siteSettings['home.about.image_alt'] ?? 'Bangunan sekolah SMK Islamiyah Ciputat');
        $schoolAddress = filled($siteSettings['about.location_address'] ?? '')
            ? $siteSettings['about.location_address']
            : (filled($siteSettings['identity.address'])
                ? $siteSettings['identity.address']
                : ($siteSettings['identity.location'] ?? 'Alamat sekolah belum tersedia'));
        $locationDescription = $siteSettings['about.location_description']
            ?? 'Temukan lokasi SMK Islamiyah Ciputat melalui peta di samping atau buka langsung di Google Maps.';
        $mapQuery = filled($siteSettings['about.map_coordinates'] ?? '')
            ? $siteSettings['about.map_coordinates']
            : $schoolAddress;
        $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($mapQuery);
        $missions = collect(preg_split('/\r\n|\r|\n/', $siteSettings['about.mission'] ?? ''))
            ->filter(fn (string $mission): bool => trim($mission) !== '')
            ->values();
    @endphp

    <section class="section public-page about-page">
        @if ($schoolImage || $profileParagraphs->isNotEmpty())
            <section class="about-profile" id="about-profile" aria-labelledby="about-profile-title">
                <div class="container public-page__container about-profile__container">
                    @if ($schoolImage)
                        <figure class="about-profile__image">
                            <img
                                src="{{ $schoolImage }}"
                                alt="{{ $profileImageAlt }}"
                                loading="lazy"
                            >
                        </figure>
                    @endif

                    <div class="about-profile__copy">
                        <div class="about-section-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m3 10 9-6 9 6"/>
                                <path d="M4 10h16"/>
                                <path d="M6 10v8M10 10v8M14 10v8M18 10v8"/>
                                <path d="M3 19h18"/>
                            </svg>
                            <span>Profil Sekolah</span>
                        </div>
                        <h2 id="about-profile-title">{{ $profileHeading }}</h2>
                        <div class="about-profile__title-accent" aria-hidden="true"></div>

                        @foreach ($profileParagraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <div class="container public-page__container about-page__container">
            <section class="about-principles" aria-label="Visi dan misi sekolah">
                <article class="about-principle about-principle--vision">
                    <div class="about-principle__heading">
                        <i data-lucide="target" aria-hidden="true"></i>
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
                        <i data-lucide="list-checks" aria-hidden="true"></i>
                        <h2>Misi Sekolah</h2>
                    </div>
                    <div class="about-principle__line" aria-hidden="true"></div>

                    @if ($missions->isNotEmpty())
                        <ul class="about-mission-list">
                            @foreach ($missions as $mission)
                                <li>
                                    <i class="about-mission-list__icon" data-lucide="circle-check" aria-hidden="true"></i>
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
                        <i class="about-location__icon" data-lucide="map-pin" aria-hidden="true"></i>
                        <h2 id="about-location-title">Lokasi Sekolah</h2>
                    </div>
                    <div class="about-principle__line about-principle__line--small" aria-hidden="true"></div>

                    <p>{{ $locationDescription }}</p>

                    <div class="about-location__address">
                        <i data-lucide="map-pinned" aria-hidden="true"></i>
                        <div>
                            <strong>{{ $schoolName }}</strong>
                            <span>{{ $schoolAddress }}</span>
                        </div>
                    </div>

                    <a class="about-location__button" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">
                        <i data-lucide="external-link" aria-hidden="true"></i>
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
