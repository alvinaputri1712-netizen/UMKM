<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_index_filters_by_published_categories(): void
    {
        $this->createArticle('Berita Akademik', 'berita-akademik', 'Akademik', now());
        $this->createArticle('Berita Kegiatan', 'berita-kegiatan', 'Kegiatan Sekolah', now()->subDay());
        $this->createArticle('Berita Draft', 'berita-draft', 'Pengumuman', now(), 'draft');

        $response = $this->get(route('news.index', ['category' => 'Akademik']));

        $response->assertOk()
            ->assertSee('Berita Akademik')
            ->assertSee(route('news.index', ['category' => 'Akademik']), false)
            ->assertDontSee('Berita Kegiatan')
            ->assertDontSee('Pengumuman');

        $this->get(route('news.index', ['category' => 'Tidak Ada']))
            ->assertNotFound();
    }

    public function test_news_index_features_the_latest_article_and_paginates_the_remaining_news(): void
    {
        for ($index = 1; $index <= 14; $index++) {
            $this->createArticle(
                'Berita '.$index,
                'berita-'.$index,
                'Kegiatan Sekolah',
                now()->subDays($index),
            );
        }

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('class="news-featured"', false)
            ->assertSee('Berita 1')
            ->assertDontSee('Berita 14')
            ->assertSee('page=2', false);

        $this->get(route('news.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Berita 14');
    }

    public function test_news_index_prefers_the_admin_selected_featured_article(): void
    {
        $featuredArticle = $this->createArticle(
            'Berita Utama Pilihan Admin',
            'berita-utama-pilihan-admin',
            'Prestasi',
            now()->subDays(3),
            'published',
            ['homepage_group' => 'featured'],
        );
        $this->createArticle(
            'Berita Terbaru Otomatis',
            'berita-terbaru-otomatis',
            'Kegiatan Sekolah',
            now(),
            'published',
            ['homepage_group' => 'auto'],
        );

        $response = $this->get(route('news.index'));
        $response->assertOk()
            ->assertSee('Berita Utama Pilihan Admin')
            ->assertSee('Berita Terbaru Otomatis');

        $content = $response->getContent() ?? '';
        $featuredStart = strpos($content, '<article class="news-featured">');
        $featuredEnd = $featuredStart === false
            ? false
            : strpos($content, '</article>', $featuredStart);
        $this->assertNotFalse($featuredStart);
        $this->assertNotFalse($featuredEnd);

        $featuredMarkup = substr($content, $featuredStart, $featuredEnd - $featuredStart);
        $this->assertStringContainsString($featuredArticle->title, $featuredMarkup);
        $this->assertStringNotContainsString('Berita Terbaru Otomatis', $featuredMarkup);
    }

    public function test_news_index_searches_published_content_and_combines_with_category_filter(): void
    {
        $this->createArticle(
            'Kegiatan Literasi Digital',
            'kegiatan-literasi-digital',
            'Kegiatan Sekolah',
            now(),
            'published',
            [],
            'Siswa mengikuti lokakarya literasi.',
        );
        $this->createArticle(
            'Kegiatan Olahraga',
            'kegiatan-olahraga',
            'Kegiatan Sekolah',
            now()->subDay(),
        );
        $this->createArticle(
            'Prestasi Literasi',
            'prestasi-literasi',
            'Prestasi',
            now()->subDays(2),
            'published',
            [],
            'Prestasi literasi siswa.',
        );
        $this->createArticle(
            'Draft Literasi',
            'draft-literasi',
            'Kegiatan Sekolah',
            now(),
            'draft',
            [],
            'Draft lokakarya literasi.',
        );

        $this->get(route('news.index', [
            'category' => 'Kegiatan Sekolah',
            'search' => 'literasi',
        ]))
            ->assertOk()
            ->assertSee('Kegiatan Literasi Digital')
            ->assertSee('name="search"', false)
            ->assertSee('value="literasi"', false)
            ->assertSee('category=Prestasi&amp;search=literasi', false)
            ->assertDontSee('Kegiatan Olahraga')
            ->assertDontSee('Prestasi Literasi')
            ->assertDontSee('Draft Literasi');

        $this->get(route('news.index', ['search' => 'tidak-ditemukan']))
            ->assertOk()
            ->assertSee('Tidak ada berita yang cocok dengan pencarian')
            ->assertDontSee('Kegiatan Literasi Digital');
    }

    public function test_news_index_renders_latest_popular_fallback_and_published_category_counts(): void
    {
        $this->createArticle(
            'Berita Terbaru',
            'berita-terbaru-portal',
            'Kegiatan Sekolah',
            now(),
            'published',
            ['author' => 'Redaksi Sekolah'],
            'Ringkasan berita terbaru.',
            '/images/berita-terbaru.jpg',
        );
        $this->createArticle(
            'Berita Lebih Lama',
            'berita-lebih-lama-portal',
            'Kegiatan Sekolah',
            now()->subDay(),
        );
        $this->createArticle(
            'Informasi Sekolah',
            'informasi-sekolah-portal',
            'Informasi Uji',
            now()->subDays(2),
        );
        $this->createArticle(
            'Draft Tidak Tampil',
            'draft-tidak-tampil-portal',
            'Pengumuman',
            now(),
            'draft',
        );

        $response = $this->get(route('news.index'));

        $response->assertOk()
            ->assertSee('<h1><span>Berita</span> <span>Sekolah</span></h1>', false)
            ->assertSee('BERITA INFORMASI SEKOLAH')
            ->assertSee('SMK Islamiyah Ciputat')
            ->assertSee('Berita Terbaru')
            ->assertSee('Redaksi Sekolah')
            ->assertSee('/images/berita-terbaru.jpg', false)
            ->assertSee('Informasi Uji')
            ->assertSee(route('news.index', ['category' => 'Kegiatan Sekolah']), false)
            ->assertDontSee('Draft Tidak Tampil');
    }

    public function test_article_detail_shows_six_latest_other_published_articles(): void
    {
        $this->travelTo('2026-11-17 10:00:00');

        $article = $this->createArticle(
            'Berita Utama',
            'berita-utama',
            'Prestasi',
            now()->subDays(10),
            'published',
            [
                'author' => 'Redaksi Sekolah',
                'images' => ['/images/berita-satu.jpg', '/images/berita-dua.jpg'],
            ],
            "Paragraf pertama.\n\nParagraf kedua.",
            '/images/berita-utama.jpg',
        );
        for ($index = 1; $index <= 7; $index++) {
            $this->createArticle(
                'Berita Lainnya '.$index,
                'berita-lainnya-'.$index,
                $index % 2 === 0 ? 'Prestasi' : 'Kegiatan Sekolah',
                now()->subDays($index),
                imagePath: '/images/berita-lainnya-'.$index.'.jpg',
            );
        }
        $this->createArticle(
            'Draft Terbaru',
            'draft-terbaru',
            'Pengumuman',
            now(),
            'draft',
            imagePath: '/images/draft-terbaru.jpg',
        );

        $response = $this->get(route('news.show', $article->slug));

        $response->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Berita')
            ->assertSee('Berita Utama')
            ->assertSee('Redaksi Sekolah')
            ->assertSee('Paragraf pertama.')
            ->assertSee('Paragraf kedua.')
            ->assertSee('/images/berita-utama.jpg', false)
            ->assertSee('/images/berita-satu.jpg', false)
            ->assertSee('/images/berita-dua.jpg', false)
            ->assertSee('Berita Sekolah')
            ->assertSee('Berita Lainnya')
            ->assertSee('Berita Terkait')
            ->assertSee('Kategori Berita')
            ->assertSee('Kegiatan Sekolah')
            ->assertSee('Prestasi')
            ->assertSee('aria-label="8 berita"', false)
            ->assertSee(route('news.index', ['category' => 'Kegiatan Sekolah']), false)
            ->assertSee('/images/berita-lainnya-1.jpg', false)
            ->assertDontSee('Berita Lainnya 7')
            ->assertDontSee('Draft Terbaru')
            ->assertDontSee('Berita Sebelumnya')
            ->assertDontSee('Berita Berikutnya')
            ->assertSee('https://wa.me/', false)
            ->assertSee('https://www.facebook.com/sharer/sharer.php', false)
            ->assertDontSee('dibaca');

        $content = $response->getContent() ?? '';
        $sectionStart = strpos($content, '<section class="news-related-articles"');
        $this->assertNotFalse($sectionStart);

        $sectionEnd = strpos($content, '</section>', $sectionStart);
        $this->assertNotFalse($sectionEnd);

        $sectionMarkup = substr($content, $sectionStart, $sectionEnd - $sectionStart);
        $this->assertSame(6, substr_count($sectionMarkup, 'class="news-related-card"'));
        $this->assertStringNotContainsString($article->slug, $sectionMarkup);
        $this->assertStringNotContainsString('/images/berita-lainnya-7.jpg', $sectionMarkup);

        $expectedUrls = [];
        for ($index = 1; $index <= 6; $index++) {
            $expectedUrls[] = route('news.show', 'berita-lainnya-'.$index);
        }

        preg_match_all('/<a class="news-related-card__link" href="([^"]+)"/', $sectionMarkup, $matches);
        $this->assertSame($expectedUrls, $matches[1]);

        $sidebarStart = strpos($content, '<aside class="news-detail-sidebar"');
        $this->assertNotFalse($sidebarStart);

        $sidebarEnd = strpos($content, '</aside>', $sidebarStart);
        $this->assertNotFalse($sidebarEnd);

        $sidebarMarkup = substr($content, $sidebarStart, $sidebarEnd - $sidebarStart);
        $this->assertSame(2, substr_count($sidebarMarkup, 'aria-label="4 berita"'));
        $this->assertStringNotContainsString('Pengumuman', $sidebarMarkup);

        $sidebarListStart = strpos($sidebarMarkup, '<ol class="news-detail-related-list">');
        $this->assertNotFalse($sidebarListStart);

        $sidebarListEnd = strpos($sidebarMarkup, '</ol>', $sidebarListStart);
        $this->assertNotFalse($sidebarListEnd);

        $sidebarListMarkup = substr($sidebarMarkup, $sidebarListStart, $sidebarListEnd - $sidebarListStart);
        $expectedSidebarUrls = [];
        for ($index = 1; $index <= 3; $index++) {
            $expectedSidebarUrls[] = route('news.show', 'berita-lainnya-'.$index);
        }

        preg_match_all('/<a class="news-detail-related-item__link" href="([^"]+)"/', $sidebarListMarkup, $sidebarMatches);
        $this->assertSame($expectedSidebarUrls, $sidebarMatches[1]);
    }

    public function test_article_detail_shows_fewer_than_six_available_related_articles(): void
    {
        $this->travelTo('2026-11-17 10:00:00');

        $article = $this->createArticle('Berita Utama', 'berita-utama', 'Prestasi', now());
        $this->createArticle(
            'Berita Terbaru',
            'berita-terbaru',
            'Kegiatan Sekolah',
            now()->subDay(),
            imagePath: '/images/berita-terbaru.jpg',
        );
        $this->createArticle(
            'Berita Sebelumnya',
            'berita-sebelumnya',
            'Prestasi',
            now()->subDays(2),
            imagePath: '/images/berita-sebelumnya.jpg',
        );

        $response = $this->get(route('news.show', $article->slug));

        $response->assertOk()
            ->assertSee('Berita Lainnya')
            ->assertSee('Berita Terbaru')
            ->assertSee('Berita Sebelumnya');

        $content = $response->getContent() ?? '';
        $sectionStart = strpos($content, '<section class="news-related-articles"');
        $this->assertNotFalse($sectionStart);

        $sectionEnd = strpos($content, '</section>', $sectionStart);
        $this->assertNotFalse($sectionEnd);

        $sectionMarkup = substr($content, $sectionStart, $sectionEnd - $sectionStart);
        $this->assertSame(2, substr_count($sectionMarkup, 'class="news-related-card"'));
    }

    public function test_news_cards_preview_video_and_article_detail_renders_all_media_items(): void
    {
        $article = $this->createArticle(
            'Berita Video Sekolah',
            'berita-video-sekolah',
            'Kegiatan Sekolah',
            now(),
            metadata: [
                'homepage_group' => 'latest',
                'media' => [
                    ['type' => 'video', 'source' => 'url', 'url' => 'https://www.youtube.com/watch?v=AbCdeFGhi12'],
                    ['type' => 'image', 'source' => 'url', 'url' => 'https://example.test/berita-1.jpg'],
                    ['type' => 'video', 'source' => 'url', 'url' => 'https://cdn.example.test/video-2.mp4'],
                    ['type' => 'video', 'source' => 'url', 'url' => 'https://cdn.example.test/video-3.webm'],
                    ['type' => 'image', 'source' => 'url', 'url' => 'https://example.test/berita-4.jpg'],
                    ['type' => 'video', 'source' => 'url', 'url' => 'https://cdn.example.test/video-5.ogv'],
                ],
            ],
        );

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/AbCdeFGhi12/hqdefault.jpg', false);

        $response = $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/AbCdeFGhi12', false)
            ->assertSee('https://cdn.example.test/video-2.mp4', false)
            ->assertSee('https://cdn.example.test/video-3.webm', false)
            ->assertSee('https://cdn.example.test/video-5.ogv', false)
            ->assertSee('https://example.test/berita-1.jpg', false)
            ->assertSee('https://example.test/berita-4.jpg', false);

        $content = $response->getContent() ?? '';
        $galleryStart = strpos($content, '<section class="news-article-gallery"');
        $this->assertNotFalse($galleryStart);
        $galleryEnd = strpos($content, '</section>', $galleryStart);
        $this->assertNotFalse($galleryEnd);
        $galleryMarkup = substr($content, $galleryStart, $galleryEnd - $galleryStart);
        $this->assertSame(5, substr_count($galleryMarkup, '<figure>'));
    }

    public function test_article_detail_without_other_published_articles_hides_related_news(): void
    {
        $article = $this->createArticle(
            'Berita Tunggal',
            'berita-tunggal',
            'Kegiatan Sekolah',
            now(),
        );

        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertDontSee('news-related-articles')
            ->assertDontSee('news-article-gallery')
            ->assertDontSee('Redaksi Sekolah');
    }

    private function createArticle(
        string $title,
        string $slug,
        string $category,
        ?Carbon $publishedAt,
        string $status = 'published',
        array $metadata = [],
        ?string $body = null,
        ?string $imagePath = null,
    ): NewsArticle {
        return NewsArticle::query()->create([
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'summary' => 'Ringkasan '.$title,
            'body' => $body,
            'image_path' => $imagePath,
            'status' => $status,
            'published_at' => $publishedAt,
            'metadata' => $metadata,
        ]);
    }
}
