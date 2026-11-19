@extends('layouts.app')

@section('title', 'Berita | '.$siteSettings['identity.school_name'])
@section('meta_description', 'Informasi terkini seputar kegiatan, prestasi, dan perkembangan SMK Islamiyah Ciputat.')

@section('content')
    @php
        $searchQueryParams = $searchTerm !== '' ? ['search' => $searchTerm] : [];
        $videoArticles = collect([$featuredArticle])
            ->filter()
            ->concat($popularArticles)
            ->concat($articles->getCollection())
            ->unique('id')
            ->filter(fn ($article) => collect($article->media_items)->contains('type', 'video'))
            ->take(8);
    @endphp

    <section class="news-index-page">
        <div class="container news-editorial__container">
            <header class="news-portal-banner">
                <div class="news-portal-banner__copy">
                    <nav class="news-portal-banner__breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}">Beranda</a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page">Berita</span>
                    </nav>
                    <span class="news-section-label news-portal-banner__label">BERITA INFORMASI SEKOLAH</span>
                    <span class="news-portal-banner__motif" aria-hidden="true">
                        <i data-lucide="newspaper"></i>
                    </span>
                    <h1><span>Berita</span> <span>Sekolah</span></h1>
                    <p>Informasi terbaru, kegiatan, prestasi, dan perkembangan SMK Islamiyah Ciputat.</p>
                </div>
            </header>

            <div class="news-portal-layout">
                    <div class="news-index-controls">
                        <nav class="news-category-filter" aria-label="Filter kategori berita">
                            <a
                                class="{{ $selectedCategory === null ? 'is-active' : '' }}"
                                href="{{ route('news.index', $searchQueryParams) }}"
                                @if ($selectedCategory === null) aria-current="page" @endif
                            >
                                <i data-lucide="grid-2x2" aria-hidden="true"></i>
                                Semua
                            </a>
                            @foreach ($categories as $category)
                                @php
                                    $categoryName = strtolower($category);
                                    $categoryIcon = match (true) {
                                        str_contains($categoryName, 'prestasi') => 'trophy',
                                        str_contains($categoryName, 'kegiatan') => 'calendar-days',
                                        str_contains($categoryName, 'informasi') || str_contains($categoryName, 'pengumuman') => 'clipboard-list',
                                        default => 'book-open',
                                    };
                                    $categoryLabel = match ($categoryName) {
                                        'kegiatan sekolah' => 'Kegiatan',
                                        'prestasi' => 'Prestasi',
                                        'pengumuman' => 'Pengumuman',
                                        'alumni' => 'Alumni',
                                        default => $category,
                                    };
                                @endphp
                                <a
                                    class="{{ $selectedCategory === $category ? 'is-active' : '' }}"
                                    href="{{ route('news.index', ['category' => $category] + $searchQueryParams) }}"
                                    @if ($selectedCategory === $category) aria-current="page" @endif
                                >
                                    <i data-lucide="{{ $categoryIcon }}" aria-hidden="true"></i>
                                    {{ $categoryLabel }}
                                </a>
                            @endforeach
                        </nav>
                        <form class="news-search" method="GET" action="{{ route('news.index') }}" role="search" aria-label="Cari berita">
                            @if ($selectedCategory !== null)
                                <input type="hidden" name="category" value="{{ $selectedCategory }}">
                            @endif
                            <input
                                type="search"
                                name="search"
                                value="{{ $searchTerm }}"
                                maxlength="100"
                                placeholder="Cari berita..."
                                aria-label="Cari judul, ringkasan, isi, atau kategori"
                            >
                            <button type="submit" aria-label="Cari">
                                <i data-lucide="search" aria-hidden="true"></i>
                            </button>
                            @if ($searchTerm !== '')
                                <a
                                    class="news-search__clear"
                                    href="{{ route('news.index', $selectedCategory !== null ? ['category' => $selectedCategory] : []) }}"
                                >Hapus</a>
                            @endif
                        </form>
                    </div>

                    @if ($featuredArticle || $popularArticles->isNotEmpty())
                        <section
                            class="news-featured-area{{ $popularArticles->isEmpty() ? ' news-featured-area--without-popular' : '' }}"
                            aria-label="Berita Pilihan"
                        >
                            @if ($featuredArticle)
                                @php
                                    $featuredMediaPreview = $featuredArticle->media_items[0] ?? null;
                                @endphp
                                <div class="news-featured-area__main">
                                    <article class="news-featured">
                                        <a
                                            class="news-featured__link{{ $featuredMediaPreview && ($featuredMediaPreview['type'] === 'image' || $featuredMediaPreview['poster']) ? '' : ' news-featured__link--without-image' }}"
                                            href="{{ route('news.show', $featuredArticle->slug) }}"
                                        >
                                            @if ($featuredMediaPreview && ($featuredMediaPreview['type'] === 'image' || $featuredMediaPreview['poster']))
                                                <figure class="news-featured__image">
                                                    <span class="news-image-fallback" data-news-image-fallback hidden aria-hidden="true">
                                                        <i data-lucide="newspaper"></i>
                                                    </span>
                                                    <img
                                                        src="{{ $siteConfiguration->imageUrl($featuredMediaPreview['type'] === 'image' ? $featuredMediaPreview['src'] : $featuredMediaPreview['poster']) }}"
                                                        alt="{{ $featuredMediaPreview['type'] === 'image' ? 'Foto' : 'Pratinjau video' }} {{ $featuredArticle->title }}"
                                                        fetchpriority="high"
                                                        data-news-image
                                                    >
                                                </figure>
                                            @else
                                                <span class="news-featured__fallback" aria-hidden="true">
                                                    @if ($featuredMediaPreview && $featuredMediaPreview['type'] === 'video')
                                                        <span>▶</span>
                                                    @else
                                                        <i data-lucide="newspaper"></i>
                                                    @endif
                                                </span>
                                            @endif
                                            <div class="news-featured__copy">
                                                @if ($featuredArticle->category)
                                                    <span class="news-card-category">{{ $featuredArticle->category }}</span>
                                                @endif
                                                <h2>{{ $featuredArticle->title }}</h2>
                                                <div class="news-featured__meta">
                                                    @if ($featuredArticle->published_at)
                                                        <span>
                                                            <i data-lucide="calendar-days" aria-hidden="true"></i>
                                                            <time datetime="{{ $featuredArticle->published_at->toDateString() }}">{{ $featuredArticle->published_at->format('d F Y') }}</time>
                                                        </span>
                                                    @endif
                                                    @if (filled(data_get($featuredArticle->metadata, 'author')))
                                                        <span>
                                                            <i data-lucide="user-round" aria-hidden="true"></i>
                                                            {{ data_get($featuredArticle->metadata, 'author') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if ($featuredArticle->summary)
                                                    <p>{{ $featuredArticle->summary }}</p>
                                                @endif
                                                <span class="news-featured__action">
                                                    Baca Selengkapnya
                                                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                        </a>
                                    </article>
                                </div>
                            @endif

                            @if ($popularArticles->isNotEmpty())
                                <aside class="news-sidebar" aria-labelledby="news-popular-heading">
                                    <h2 id="news-popular-heading">Berita Pilihan</h2>
                                    <ol class="news-popular-list">
                                        @foreach ($popularArticles as $article)
                                            @php
                                                $popularMediaPreview = $article->media_items[0] ?? null;
                                            @endphp
                                            <li class="news-popular-item">
                                                <a class="news-popular-item__link" href="{{ route('news.show', $article->slug) }}">
                                                    <span class="news-popular-item__image">
                                                        @if ($popularMediaPreview && ($popularMediaPreview['type'] === 'image' || $popularMediaPreview['poster']))
                                                            <img
                                                                src="{{ $siteConfiguration->imageUrl($popularMediaPreview['type'] === 'image' ? $popularMediaPreview['src'] : $popularMediaPreview['poster']) }}"
                                                                alt="{{ $popularMediaPreview['type'] === 'image' ? 'Foto' : 'Pratinjau video' }} {{ $article->title }}"
                                                                loading="lazy"
                                                            >
                                                        @else
                                                            <span class="news-popular-item__placeholder" aria-hidden="true">
                                                                <i data-lucide="newspaper"></i>
                                                            </span>
                                                        @endif
                                                    </span>
                                                    <span class="news-popular-item__copy">
                                                        @if ($article->category)
                                                            <span class="news-popular-item__category">{{ $article->category }}</span>
                                                        @endif
                                                        <strong>{{ $article->title }}</strong>
                                                        @if ($article->published_at)
                                                            <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d F Y') }}</time>
                                                        @endif
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ol>
                                </aside>
                            @endif
                        </section>
                    @endif

                    @if ($videoArticles->isNotEmpty())
                        <section class="news-video-strip" aria-labelledby="news-video-strip-heading">
                            <header class="news-video-strip__heading">
                                <h2 id="news-video-strip-heading">Video Berita</h2>
                            </header>
                            <div class="news-video-strip__items">
                                @foreach ($videoArticles as $article)
                                    @php
                                        $videoMedia = collect($article->media_items)->first(fn (array $media): bool => $media['type'] === 'video');
                                    @endphp
                                    <a class="news-video-strip__item" href="{{ route('news.show', $article->slug) }}">
                                        <span class="news-video-strip__image">
                                            @if ($videoMedia && $videoMedia['poster'])
                                                <img
                                                    src="{{ $siteConfiguration->imageUrl($videoMedia['poster']) }}"
                                                    alt=""
                                                    loading="lazy"
                                                >
                                            @else
                                                <span class="news-video-strip__placeholder" aria-hidden="true">
                                                    <i data-lucide="video"></i>
                                                </span>
                                            @endif
                                            <span class="news-video-strip__play" aria-hidden="true">
                                                <i data-lucide="play"></i>
                                            </span>
                                        </span>
                                        <strong>{{ $article->title }}</strong>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($articles->isNotEmpty() || $featuredArticle)
                        <div class="news-recommendations">
                            <div class="news-recommendations__main">
                                <section class="news-latest-section" aria-labelledby="news-latest-heading">
                                    <header class="news-latest-heading">
                                        <h2 id="news-latest-heading">Berita Terbaru</h2>
                                    </header>
                                    <div class="news-card-grid">
                                        @foreach ($articles as $article)
                                            @continue($popularArticles->contains('id', $article->id))
                                            @php
                                                $articleMediaPreview = $article->media_items[0] ?? null;
                                            @endphp
                                            <article class="news-card">
                                                <a class="news-card__link" href="{{ route('news.show', $article->slug) }}">
                                                    @if ($articleMediaPreview && ($articleMediaPreview['type'] === 'image' || $articleMediaPreview['poster']))
                                                        <figure class="news-card__image">
                                                            <span class="news-image-fallback" data-news-image-fallback hidden aria-hidden="true">
                                                                <i data-lucide="newspaper"></i>
                                                            </span>
                                                            <img
                                                                src="{{ $siteConfiguration->imageUrl($articleMediaPreview['type'] === 'image' ? $articleMediaPreview['src'] : $articleMediaPreview['poster']) }}"
                                                                alt="{{ $articleMediaPreview['type'] === 'image' ? 'Foto' : 'Pratinjau video' }} {{ $article->title }}"
                                                                loading="lazy"
                                                                data-news-image
                                                            >
                                                            @if ($article->category)
                                                                <span class="news-card-category news-card-category--photo">{{ $article->category }}</span>
                                                            @endif
                                                        </figure>
                                                    @else
                                                        <span class="news-card__image news-card__image--empty" aria-hidden="true">
                                                            @if ($articleMediaPreview && $articleMediaPreview['type'] === 'video')
                                                                <span>▶</span>
                                                            @else
                                                                <i data-lucide="newspaper"></i>
                                                            @endif
                                                            @if ($article->category)
                                                                <span class="news-card-category news-card-category--photo">{{ $article->category }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                    <div class="news-card__copy">
                                                        <h2>{{ $article->title }}</h2>
                                                        <div class="news-card-meta">
                                                            @if ($article->published_at)
                                                                <span class="news-card-date">
                                                                    <i data-lucide="calendar-days" aria-hidden="true"></i>
                                                                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d F Y') }}</time>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <span class="news-card__action">
                                                            Baca Selengkapnya
                                                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                                                        </span>
                                                    </div>
                                                </a>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            </div>

                            @if ($categoryCounts->isNotEmpty())
                                <aside class="news-category-tags" aria-labelledby="news-category-tags-heading">
                                    <h2 id="news-category-tags-heading">Kategori Populer</h2>
                                    <ul>
                                        @foreach ($categoryCounts->sortDesc()->take(6) as $popularCategory => $articleCount)
                                            <li>
                                                <a href="{{ route('news.index', ['category' => $popularCategory] + $searchQueryParams) }}">
                                                    <span aria-hidden="true">#</span>
                                                    <strong>{{ $popularCategory }}</strong>
                                                    <span>{{ $articleCount }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </aside>
                            @endif
                        </div>
                        <div class="news-pagination">{{ $articles->links() }}</div>
                    @elseif (! $featuredArticle)
                        <p class="news-empty-state">
                            <i data-lucide="newspaper" aria-hidden="true"></i>
                            @if ($searchTerm !== '')
                                Tidak ada berita yang cocok dengan pencarian {{ $searchTerm }}.
                            @else
                                Belum ada berita yang dipublikasikan.
                            @endif
                        </p>
                    @endif
            </div>
        </div>
    </section>
@endsection
