@extends('layouts.app')

@section('title', $siteSettings['spmb.title'].' '.$siteSettings['spmb.year'])
@section('meta_description', $siteSettings['spmb.description'])

@section('content')
    @php
        $requirements = collect(preg_split('/\r\n|\r|\n/', $siteSettings['spmb.requirements']))
            ->filter(fn (string $item): bool => trim($item) !== '');
        $steps = collect(preg_split('/\r\n|\r|\n/', $siteSettings['spmb.steps']))
            ->filter(fn (string $item): bool => trim($item) !== '');
        $committeeContact = trim((string) $siteSettings['spmb.contact']);
        $committeeWhatsapp = preg_replace('/\D+/', '', $committeeContact) ?? '';

        if (str_starts_with($committeeWhatsapp, '0')) {
            $committeeWhatsapp = '62'.substr($committeeWhatsapp, 1);
        } elseif (str_starts_with($committeeWhatsapp, '8')) {
            $committeeWhatsapp = '62'.$committeeWhatsapp;
        }
    @endphp
    <section class="public-page--spmb" aria-labelledby="spmb-title">
        <div class="container spmb-hero">
            <div class="spmb-hero__main">
                <div class="spmb-hero__copy">
                    <span class="spmb-eyebrow">
                        SPMB
                        @if (filled($siteSettings['spmb.year']))
                            <span aria-hidden="true">TAHUN AJARAN</span> {{ $siteSettings['spmb.year'] }}
                        @endif
                    </span>
                    <h1 id="spmb-title">{{ $siteSettings['spmb.title'] }}</h1>
                    <span class="spmb-title-accent" aria-hidden="true"></span>
                    <p class="spmb-hero__description">{{ $siteSettings['spmb.description'] }}</p>
                    <p class="spmb-status {{ $siteSettings['spmb.open'] ? 'spmb-status--open' : 'spmb-status--closed' }}" role="status">
                        <span class="spmb-status__dot" aria-hidden="true"></span>
                        {{ $siteSettings['spmb.open'] ? 'Pendaftaran sedang dibuka' : 'Pendaftaran saat ini belum dibuka' }}
                    </p>
                    <div class="spmb-hero__actions">
                        <a class="spmb-button spmb-button--primary" href="{{ $siteSettings['spmb.information_url'] }}" target="_blank" rel="noopener noreferrer">
                            <i data-lucide="clipboard-list" aria-hidden="true"></i>
                            <span>Informasi SPMB</span>
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </a>
                        <a class="spmb-button spmb-button--secondary" href="#spmb-schedule">
                            <i data-lucide="calendar-days" aria-hidden="true"></i>
                            <span>Jadwal Pendaftaran</span>
                        </a>
                        @if ($siteSettings['spmb.form_url'] && $siteSettings['spmb.open'])
                            <a class="spmb-form-link" href="{{ $siteSettings['spmb.form_url'] }}" target="_blank" rel="noopener noreferrer">Buka formulir pendaftaran</a>
                        @endif
                    </div>
                    @if ($committeeContact !== '')
                        <div class="spmb-contact">
                            <span class="spmb-contact__label">Kontak panitia</span>
                            @if ($committeeWhatsapp !== '')
                                <a class="spmb-contact__link" href="https://wa.me/{{ $committeeWhatsapp }}" target="_blank" rel="noopener noreferrer">
                                    <i data-lucide="phone" aria-hidden="true"></i>
                                    <span>{{ $committeeContact }}</span>
                                    <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="spmb-contact__value">{{ $committeeContact }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="spmb-hero__visual">
                    <span class="spmb-hero__accent" aria-hidden="true"></span>
                    <figure class="spmb-hero__photo">
                        <img
                            src="{{ asset('images/bagorundmotivtenagapendidik/bagorundtenagapendidik.png') }}"
                            alt="Gedung SMK Islamiyah Ciputat"
                            fetchpriority="high"
                        >
                    </figure>
                </div>
            </div>

            <ul class="spmb-benefits" aria-label="Keunggulan sekolah">
                <li>
                    <i data-lucide="graduation-cap" aria-hidden="true"></i>
                    <span>Pendidikan Kejuruan <strong>Berkualitas</strong></span>
                </li>
                <li>
                    <i data-lucide="settings-2" aria-hidden="true"></i>
                    <span>Fasilitas Praktik <strong>Lengkap</strong></span>
                </li>
                <li>
                    <i data-lucide="users-round" aria-hidden="true"></i>
                    <span>Tenaga Pendidik <strong>Profesional</strong></span>
                </li>
                <li>
                    <i data-lucide="trophy" aria-hidden="true"></i>
                    <span>Siap Terjun ke <strong>Dunia Kerja</strong></span>
                </li>
            </ul>
        </div>
    </section>

    @if ($steps->isNotEmpty())
        <section class="spmb-steps-section" aria-labelledby="spmb-steps-title">
            <div class="container">
                <header class="spmb-details__heading">
                    <span>Proses pendaftaran</span>
                    <h2 id="spmb-steps-title">Alur pendaftaran SPMB</h2>
                </header>
                <ol class="spmb-steps">
                    @foreach ($steps as $step)
                        <li>
                            <span class="spmb-steps__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>{{ trim($step) }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <section class="spmb-details" id="spmb-schedule" aria-labelledby="spmb-details-title">
        <div class="container">
            <header class="spmb-details__heading">
                <span>Informasi penting</span>
                <h2 id="spmb-details-title">Persiapan pendaftaran</h2>
            </header>
            <div class="spmb-info-grid">
                @if ($siteSettings['spmb.schedule'])
                    <article class="spmb-info-card">
                        <span class="spmb-info-card__eyebrow">Waktu</span>
                        <h3>Jadwal pendaftaran</h3>
                        <p class="spmb-info-card__text">{{ $siteSettings['spmb.schedule'] }}</p>
                    </article>
                @else
                    <article class="spmb-info-card">
                        <span class="spmb-info-card__eyebrow">Waktu</span>
                        <h3>Jadwal pendaftaran</h3>
                        <p class="spmb-info-card__text">Jadwal pendaftaran akan diperbarui setelah informasi resmi tersedia.</p>
                    </article>
                @endif
                @if ($requirements->isNotEmpty())
                    <article class="spmb-info-card">
                        <span class="spmb-info-card__eyebrow">Dokumen</span>
                        <h3>Persyaratan</h3>
                        <ul class="spmb-info-card__list">
                            @foreach ($requirements as $requirement)
                                <li>{{ trim($requirement) }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endif
                @if ($siteSettings['spmb.fee'])
                    <article class="spmb-info-card">
                        <span class="spmb-info-card__eyebrow">Pembiayaan</span>
                        <h3>Informasi biaya</h3>
                        <p class="spmb-info-card__text">{{ $siteSettings['spmb.fee'] }}</p>
                    </article>
                @endif
            </div>
        </div>
    </section>
@endsection