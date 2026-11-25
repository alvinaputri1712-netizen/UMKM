@extends('layouts.app')

@section('title', $siteSettings['bkk.title'])
@section('meta_description', $siteSettings['bkk.description'])

@section('content')
    <section class="section public-page public-page--bkk-jobs bkk-jobs-page">
        <div class="container public-page__container bkk-jobs-container">
            <header class="bkk-jobs-hero">
                <div class="bkk-jobs-hero__copy">
                    <span class="bkk-jobs-eyebrow">
                        <i data-lucide="briefcase-business" aria-hidden="true"></i>
                        BURSA KERJA KHUSUS (BKK)
                    </span>
                    <h1><span>Lowongan</span><span class="bkk-jobs-title__accent">Pekerjaan</span></h1>
                    <p>Informasi terbaru mengenai lowongan pekerjaan dari perusahaan mitra SMK Islamiyah Ciputat.</p>
                    <nav class="bkk-jobs-tabs" aria-label="Navigasi Bursa Kerja Khusus">
                        <a class="is-active" href="{{ route('bkk.jobs') }}" aria-current="page">
                            <i data-lucide="briefcase-business" aria-hidden="true"></i>
                            Lowongan Tersedia
                        </a>
                        <a href="{{ route('bkk.tracer-study') }}">
                            <i data-lucide="graduation-cap" aria-hidden="true"></i>
                            Tracer Study
                        </a>
                    </nav>
                </div>
                <div class="bkk-jobs-hero__media">
                    <img
                        src="{{ asset('images/10.jpeg') }}"
                        alt="Siswa SMK Islamiyah Ciputat berfoto bersama di lingkungan sekolah"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <svg class="bkk-jobs-hero__wave" viewBox="0 0 820 180" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                        <path fill="#0B3025" d="M170 180c122-4 187-69 299-55 99 13 193 1 351-87v142H170Z"/>
                        <path d="M184 171c121-4 188-67 287-55 105 13 197-1 349-84" fill="none" stroke="#E9B526" stroke-width="9"/>
                    </svg>
                </div>
            </header>

            <section class="bkk-jobs-stats" aria-label="Statistik lowongan kerja aktif">
                <div class="bkk-jobs-stat bkk-jobs-stat--green">
                    <span class="bkk-jobs-stat__icon"><i data-lucide="briefcase-business" aria-hidden="true"></i></span>
                    <span class="bkk-jobs-stat__content">
                        <strong>{{ number_format($activeJobsCount, 0, ',', '.') }}</strong>
                        <small>Lowongan Aktif</small>
                    </span>
                </div>
                <div class="bkk-jobs-stat bkk-jobs-stat--gold">
                    <span class="bkk-jobs-stat__icon"><i data-lucide="building-2" aria-hidden="true"></i></span>
                    <span class="bkk-jobs-stat__content">
                        <strong>{{ number_format($partnerCompaniesCount, 0, ',', '.') }}</strong>
                        <small>Perusahaan Mitra</small>
                    </span>
                </div>
                <p class="bkk-jobs-stats__note">Jumlah dihitung dari lowongan terpublikasi yang belum melewati batas lamaran.</p>
            </section>

            <div class="bkk-jobs-main">
                <aside class="bkk-jobs-sidebar" aria-label="Pencarian dan filter lowongan">
                    <form class="bkk-jobs-filter-form" method="GET" action="{{ route('bkk.jobs') }}">
                        <section class="bkk-jobs-search-card" aria-labelledby="bkk-jobs-search-title">
                            <div class="bkk-jobs-search-card__heading">
                                <i data-lucide="search" aria-hidden="true"></i>
                                <div>
                                    <h2 id="bkk-jobs-search-title">Cari Lowongan</h2>
                                    <p>Temukan lowongan yang sesuai dengan keahlian kamu.</p>
                                </div>
                            </div>
                            <label class="bkk-jobs-search-field" for="bkk-jobs-search">
                                <span class="sr-only">Cari posisi atau perusahaan</span>
                                <input
                                    id="bkk-jobs-search"
                                    type="search"
                                    name="search"
                                    value="{{ $searchTerm }}"
                                    placeholder="Cari posisi atau perusahaan..."
                                    maxlength="100"
                                >
                                <button type="submit" aria-label="Cari lowongan">
                                    <i data-lucide="search" aria-hidden="true"></i>
                                </button>
                            </label>
                        </section>

                        @if ($jobCategories->isNotEmpty() || $jobCompanies->isNotEmpty())
                            <section class="bkk-jobs-filter-card" aria-labelledby="bkk-jobs-filter-title">
                                <h2 id="bkk-jobs-filter-title">
                                    <i data-lucide="filter" aria-hidden="true"></i>
                                    Filter Lowongan
                                </h2>
                                @if ($jobCategories->isNotEmpty())
                                    <label class="bkk-jobs-select-field" for="bkk-jobs-category">
                                        <span>Kategori Lowongan</span>
                                        <select id="bkk-jobs-category" name="category" data-bkk-filter>
                                            <option value="">Semua Kategori</option>
                                            @foreach ($jobCategories as $category)
                                                <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif
                                @if ($jobCompanies->isNotEmpty())
                                    <label class="bkk-jobs-select-field" for="bkk-jobs-company">
                                        <span>Perusahaan</span>
                                        <select id="bkk-jobs-company" name="company" data-bkk-filter>
                                            <option value="">Semua Perusahaan</option>
                                            @foreach ($jobCompanies as $company)
                                                <option value="{{ $company }}" @selected($selectedCompany === $company)>{{ $company }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif
                                <a class="bkk-jobs-reset" href="{{ route('bkk.jobs') }}">
                                    <i data-lucide="refresh-cw" aria-hidden="true"></i>
                                    Reset Filter
                                </a>
                            </section>
                        @endif
                    </form>
                </aside>

                <section class="bkk-jobs-list" aria-labelledby="bkk-jobs-list-title">
                    <header class="bkk-jobs-list__heading">
                        <h2 id="bkk-jobs-list-title">Daftar Lowongan Pekerjaan</h2>
                        <p>Berbagai peluang kerja dari perusahaan mitra untuk alumni SMK Islamiyah Ciputat.</p>
                    </header>

                    @if ($jobs->isNotEmpty())
                        <div class="bkk-job-list">
                            @foreach ($jobs as $job)
                                @php
                                    $company = trim((string) data_get($job->metadata, 'company', ''));
                                    $position = data_get($job->metadata, 'position') ?: $job->title;
                                    $closingDate = data_get($job->metadata, 'closing_date');
                                    $applicationUrl = data_get($job->metadata, 'application_url');
                                    $companyImage = $siteConfiguration->imageUrl($job->image_path);
                                    $companyInitials = collect(preg_split('/\s+/', $company, -1, PREG_SPLIT_NO_EMPTY) ?: [])
                                        ->take(2)
                                        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
                                        ->implode('');
                                @endphp
                                <article class="bkk-job-card">
                                    <div class="bkk-job-card__logo">
                                        @if ($companyImage)
                                            <img src="{{ $companyImage }}" alt="" loading="lazy" decoding="async">
                                        @elseif ($companyInitials !== '')
                                            <span aria-hidden="true">{{ $companyInitials }}</span>
                                        @else
                                            <i data-lucide="building-2" aria-hidden="true"></i>
                                        @endif
                                    </div>

                                    <div class="bkk-job-card__information">
                                        <h3>{{ $position }}</h3>
                                        @if ($company !== '')
                                            <p class="bkk-job-card__company">
                                                <i data-lucide="building-2" aria-hidden="true"></i>
                                                {{ $company }}
                                            </p>
                                        @endif
                                        @if (filled($job->category))
                                            <span class="bkk-job-card__category">{{ $job->category }}</span>
                                        @endif
                                        @if (filled($job->summary))
                                            <p class="bkk-job-card__summary">{{ $job->summary }}</p>
                                        @endif
                                    </div>

                                    <div class="bkk-job-card__deadline">
                                        <span class="bkk-job-card__deadline-label">
                                            <i data-lucide="calendar-days" aria-hidden="true"></i>
                                            Batas Lamar
                                        </span>
                                        @if (filled($closingDate))
                                            @php($closingDateValue = \Illuminate\Support\Carbon::parse($closingDate))
                                            <time datetime="{{ $closingDateValue->toDateString() }}">{{ $closingDateValue->format('d/m/Y') }}</time>
                                        @else
                                            <span class="bkk-job-card__deadline-missing">Tidak dicantumkan</span>
                                        @endif
                                    </div>

                                    <div class="bkk-job-card__action">
                                        @if (filled($applicationUrl))
                                            <a class="bkk-job-card__link" href="{{ $applicationUrl }}" target="_blank" rel="noopener noreferrer">
                                                Lihat Detail
                                                <i data-lucide="arrow-right" aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <span class="bkk-job-card__application-note">Link pendaftaran belum tersedia</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        @if ($jobs->hasPages())
                            <div class="bkk-jobs-pagination">{{ $jobs->links() }}</div>
                        @endif
                    @else
                        <section class="bkk-jobs-empty" aria-labelledby="bkk-jobs-empty-title">
                            <span class="bkk-jobs-empty__icon" aria-hidden="true"><i data-lucide="briefcase-business"></i></span>
                            <div>
                                <h3 id="bkk-jobs-empty-title">
                                    @if ($searchTerm !== '' || $selectedCategory !== '' || $selectedCompany !== '')
                                        Lowongan Tidak Ditemukan
                                    @else
                                        Belum Ada Lowongan Pekerjaan
                                    @endif
                                </h3>
                                <p>
                                    @if ($searchTerm !== '' || $selectedCategory !== '' || $selectedCompany !== '')
                                        Coba ubah kata pencarian atau filter yang dipilih.
                                    @else
                                        Informasi lowongan akan diperbarui ketika tersedia. Silakan kembali lagi untuk melihat peluang kerja terbaru.
                                    @endif
                                </p>
                            </div>
                        </section>
                    @endif
                </section>
            </div>
        </div>
    </section>
@endsection
