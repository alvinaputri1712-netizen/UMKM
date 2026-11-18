@extends('layouts.app')

@section('title', 'Berita | '.$siteSettings['identity.school_name'])
@section('meta_description', 'Informasi terkini seputar kegiatan, prestasi, dan perkembangan SMK Islamiyah Ciputat.')

@section('content')
    @php
        $newsBannerImage = $siteConfiguration->imageUrl($siteSettings['about.image']);
        $newsBannerAlt = $siteSettings['home.about.image_alt'];
        $searchQueryParams = $searchTerm !== '' ? ['search' => $searchTerm] : [];
    @endphp

    <section class="news-index-page">
        <div class="container news-editorial__container">
            <header class="news-portal-banner">
                <div class="news-portal-banner__copy">
                    <span class="news-portal-banner__motif" aria-hidden="true">
                        <i data-lucide="newspaper"></i>
                    </span>
                    <span class="news-portal-banner__label">
                        <i data-lucide="newspaper" aria-hidden="true"></i>
                        Berita Sekolah
                    </span>
                    <h1>Portal Berita<br>SMK Islamiyah Ciputat</h1>
                    <p>Informasi terkini seputar kegiatan, prestasi, dan perkembangan SMK Islamiyah Ciputat.</p>
                </div>
                @if ($newsBannerImage)
                    <figure class="news-portal-banner__image">
                        <img src="{{ $newsBannerImage }}" alt="{{ $newsBannerAlt }}" fetchpriority="high">
                    </figure>
                @endif
            </header>

            <div class="news-portal-layout{{ $featuredArticle ? '' : ' news-portal-layout--without-featured' }}">
                    @if ($featuredArticle)
                        @php
                            $featuredMediaPreview = $featuredArticle->media_items[0] ?? null;
                        @endphp
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
                    @endif

                    <aside class="news-sidebar" aria-label="Informasi berita lainnya">
                        <section class="news-sidebar-card" aria-labelledby="news-popular-title">
                            <h2 class="news-sidebar-card__title" id="news-popular-title">
                                <i data-lucide="flame" aria-hidden="true"></i>
                                Berita Terpopuler
                            </h2>
                            @if ($popularArticles->isNotEmpty())
                                <ol class="news-popular-list">
                                    @foreach ($popularArticles as $article)
                                        @php
                                            $articleMediaPreview = $article->media_items[0] ?? null;
                                        @endphp
                                        <li class="news-popular-item">
                                            <span class="news-popular-item__rank" aria-label="Urutan {{ $loop->iteration }}">{{ $loop->iteration }}</span>
                                            <a href="{{ route('news.show', $article->slug) }}">
                                                @if ($articleMediaPreview && ($articleMediaPreview['type'] === 'image' || $articleMediaPreview['poster']))
                                                    <span class="news-popular-item__image">
                                                        <span class="news-image-fallback" data-news-image-fallback hidden aria-hidden="true">
                                                            <i data-lucide="newspaper"></i>
                                                        </span>
                                                        <img
                                                            src="{{ $siteConfiguration->imageUrl($articleMediaPreview['type'] === 'image' ? $articleMediaPreview['src'] : $articleMediaPreview['poster']) }}"
                                                            alt=""
                                                            loading="lazy"
                                                            data-news-image
                                                        >
                                                    </span>
                                                @else
                                                    <span class="news-popular-item__image news-popular-item__image--empty" aria-hidden="true">
                                                        <i data-lucide="newspaper"></i>
                                                    </span>
                                                @endif
                                                <span class="news-popular-item__copy">
                                                    <strong>{{ $article->title }}</strong>
                                                    @if ($article->published_at)
                                                        <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d F Y') }}</time>
                                                    @endif
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="news-sidebar-card__empty">Belum ada berita yang dipublikasikan.</p>
                            @endif
                        </section>

                        <section class="news-sidebar-card" aria-labelledby="news-categories-title">
                            <h2 class="news-sidebar-card__title" id="news-categories-title">
                                <i data-lucide="folder" aria-hidden="true"></i>
                                Kategori Berita
                            </h2>
                            <ul class="news-sidebar-categories">
                                <li>
                                    <a
                                        class="{{ $selectedCategory === null ? 'is-active' : '' }}"
                                        href="{{ route('news.index', $searchQueryParams) }}"
                                        @if ($selectedCategory === null) aria-current="page" @endif
                                    >
                                        <i data-lucide="grid-2x2" aria-hidden="true"></i>
                                        <span>Semua Berita</span>
                                        <span class="news-sidebar-categories__count" aria-label="{{ $publishedArticleCount }} berita">{{ $publishedArticleCount }}</span>
                                    </a>
                                </li>
                                @foreach ($categories as $category)
                                    @php
                                        $categoryName = strtolower($category);
                                        $categoryIcon = match (true) {
                                            str_contains($categoryName, 'prestasi') => 'trophy',
                                            str_contains($categoryName, 'kegiatan') => 'calendar-days',
                                            str_contains($categoryName, 'informasi') || str_contains($categoryName, 'pengumuman') => 'clipboard-list',
                                            default => 'book-open',
                                        };
                                    @endphp
                                    <li>
                                        <a
                                            class="{{ $selectedCategory === $category ? 'is-active' : '' }}"
                                            href="{{ route('news.index', ['category' => $category] + $searchQueryParams) }}"
                                            @if ($selectedCategory === $category) aria-current="page" @endif
                                        >
                                            <i data-lucide="{{ $categoryIcon }}" aria-hidden="true"></i>
                                            <span>{{ $category }}</span>
                                            <span class="news-sidebar-categories__count" aria-label="{{ $categoryCounts->get($category, 0) }} berita">{{ $categoryCounts->get($category, 0) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    </aside>

                    <div class="news-index-controls">
                        <nav class="news-category-filter" aria-label="Filter kategori berita">
                            <a
                                class="{{ $selectedCategory === null ? 'is-active' : '' }}"
                                href="{{ route('news.index', $searchQueryParams) }}"
                                @if ($selectedCategory === null) aria-current="page" @endif
                            >
                                <i data-lucide="grid-2x2" aria-hidden="true"></i>
                                Semua Berita
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
                                @endphp
                                <a
                                    class="{{ $selectedCategory === $category ? 'is-active' : '' }}"
                                    href="{{ route('news.index', ['category' => $category] + $searchQueryParams) }}"
                                    @if ($selectedCategory === $category) aria-current="page" @endif
                                >
                                    <i data-lucide="{{ $categoryIcon }}" aria-hidden="true"></i>
                                    {{ $category }}
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
                            <button type="submit">Cari</button>
                            @if ($searchTerm !== '')
                                <a
                                    class="news-search__clear"
                                    href="{{ route('news.index', $selectedCategory !== null ? ['category' => $selectedCategory] : []) }}"
                                >Hapus</a>
                            @endif
                        </form>
                    </div>

                    @if ($articles->isNotEmpty())
                        <div class="news-card-grid">
                            @foreach ($articles as $article)
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
                                            </figure>
                                        @else
                                            <span class="news-card__image news-card__image--empty" aria-hidden="true">
                                                @if ($articleMediaPreview && $articleMediaPreview['type'] === 'video')
                                                    <span>▶</span>
                                                @else
                                                    <i data-lucide="newspaper"></i>
                                                @endif
                                            </span>
                                        @endif
                                        <div class="news-card__copy">
                                            <div class="news-card-meta">
                                                @if ($article->category)
                                                    <span class="news-card-category">{{ $article->category }}</span>
                                                @endif
                                                @if ($article->published_at)
                                                    <span class="news-card-date">
                                                        <i data-lucide="calendar-days" aria-hidden="true"></i>
                                                        <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d F Y') }}</time>
                                                    </span>
                                                @endif
                                            </div>
                                            <h2>{{ $article->title }}</h2>
                                            @if ($article->summary)
                                                <p>{{ $article->summary }}</p>
                                            @endif
                                            <span class="news-card__action">
                                                Baca Selengkapnya
                                                <i data-lucide="arrow-right" aria-hidden="true"></i>
                                            </span>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
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
