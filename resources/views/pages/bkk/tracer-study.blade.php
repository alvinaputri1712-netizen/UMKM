@extends('layouts.app')

@section('title', $siteSettings['bkk.tracer_title'])
@section('meta_description', $siteSettings['bkk.tracer_description'])

@section('content')
    @php
        $tracerTitleParts = preg_split('/\s+/', trim($siteSettings['bkk.tracer_title']), 2) ?: [];
    @endphp

    <section class="section public-page public-page--tracer tracer-study-page">
        <div class="container tracer-study-container">
            <header class="tracer-study-hero" aria-labelledby="tracer-study-title">
                <div class="tracer-study-hero__copy">
                    <span class="tracer-study-eyebrow">
                        <i data-lucide="briefcase-business" aria-hidden="true"></i>
                        BURSA KERJA KHUSUS (BKK)
                    </span>
                    <h1 id="tracer-study-title">
                        <span>{{ $tracerTitleParts[0] ?? $siteSettings['bkk.tracer_title'] }}</span>
                        @if (filled($tracerTitleParts[1] ?? null))
                            <span class="tracer-study-title__accent">{{ $tracerTitleParts[1] }}</span>
                        @endif
                    </h1>
                    <p class="tracer-study-hero__description">
                        Sistem pendataan alumni SMK Islamiyah Ciputat untuk mengetahui jejak karier, pendidikan lanjutan, dan kontribusi alumni di dunia kerja maupun masyarakat.
                    </p>
                    @if (filled($siteSettings['bkk.tracer_url']))
                        <div class="tracer-study-actions">
                            <a class="tracer-study-action tracer-study-action--primary" href="{{ $siteSettings['bkk.tracer_url'] }}" target="_blank" rel="noopener noreferrer">
                                <i data-lucide="file-pen-line" aria-hidden="true"></i>
                                Isi Tracer Study Sekarang
                                <i data-lucide="arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @else
                        <p class="tracer-study-form-note">Formulir tracer study belum tersedia saat ini.</p>
                    @endif
                </div>
                <div class="tracer-study-hero__media">
                    <img
                        src="{{ asset('images/10.jpeg') }}"
                        alt="Siswa SMK Islamiyah Ciputat berfoto bersama di lingkungan sekolah"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
            </header>

            <section class="tracer-study-stats" aria-label="Statistik alumni">
                <div class="tracer-study-stat tracer-study-stat--green">
                    <span class="tracer-study-stat__icon"><i data-lucide="users" aria-hidden="true"></i></span>
                    <span class="tracer-study-stat__copy"><strong>—</strong><small>Total Alumni</small></span>
                </div>
                <div class="tracer-study-stat tracer-study-stat--gold">
                    <span class="tracer-study-stat__icon"><i data-lucide="briefcase-business" aria-hidden="true"></i></span>
                    <span class="tracer-study-stat__copy"><strong>—</strong><small>Sudah Bekerja</small></span>
                </div>
                <div class="tracer-study-stat tracer-study-stat--green">
                    <span class="tracer-study-stat__icon"><i data-lucide="graduation-cap" aria-hidden="true"></i></span>
                    <span class="tracer-study-stat__copy"><strong>—</strong><small>Melanjutkan Pendidikan</small></span>
                </div>
                <div class="tracer-study-stat tracer-study-stat--gold">
                    <span class="tracer-study-stat__icon"><i data-lucide="chart-no-axes-column-increasing" aria-hidden="true"></i></span>
                    <span class="tracer-study-stat__copy"><strong>—</strong><small>Wirausaha</small></span>
                </div>
                <p class="tracer-study-stats__note">Data statistik alumni belum tersedia.</p>
            </section>

            <section class="tracer-study-goals" id="tujuan-tracer-study" aria-labelledby="tracer-study-goals-title">
                <div class="tracer-study-goals__intro">
                    <h2 id="tracer-study-goals-title">Tujuan Tracer Study</h2>
                    <p>Menelusuri dan memetakan perkembangan alumni sebagai bahan evaluasi dan peningkatan kualitas pendidikan di SMK Islamiyah Ciputat.</p>
                </div>
                <div class="tracer-study-goals__grid">
                    <article class="tracer-study-goal">
                        <span class="tracer-study-goal__icon"><i data-lucide="users" aria-hidden="true"></i></span>
                        <h3>Mengetahui Status Alumni</h3>
                    </article>
                    <article class="tracer-study-goal">
                        <span class="tracer-study-goal__icon"><i data-lucide="chart-no-axes-column-increasing" aria-hidden="true"></i></span>
                        <h3>Evaluasi Mutu Pendidikan</h3>
                    </article>
                    <article class="tracer-study-goal">
                        <span class="tracer-study-goal__icon"><i data-lucide="handshake" aria-hidden="true"></i></span>
                        <h3>Memperkuat Jaringan Alumni</h3>
                    </article>
                    <article class="tracer-study-goal">
                        <span class="tracer-study-goal__icon"><i data-lucide="book-open" aria-hidden="true"></i></span>
                        <h3>Meningkatkan Kualitas Lulusan</h3>
                    </article>
                </div>
            </section>

            @if (filled($siteSettings['bkk.cooperation']))
                <section class="tracer-study-cooperation" aria-labelledby="tracer-study-cooperation-title">
                    <h2 id="tracer-study-cooperation-title">Informasi Kerja Sama</h2>
                    <div class="public-page__body">{{ $siteSettings['bkk.cooperation'] }}</div>
                </section>
            @endif
        </div>
        <svg class="tracer-study-wave" viewBox="0 0 1440 180" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path fill="#DCECE1" d="M0 78C190 142 336 145 510 104c183-43 314-54 482-5 158 46 302 31 448-29v110H0V78Z"/>
            <path fill="#F8E8B3" d="M835 180c194-80 354-112 605-61v61H835Z"/>
            <path fill="#103D2E" d="M0 142c227 42 451 40 675-4 231-46 474-55 765-17v59H0v-38Z"/>
        </svg>
    </section>
@endsection