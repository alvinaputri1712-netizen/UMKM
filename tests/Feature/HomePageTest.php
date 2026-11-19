<?php

namespace Tests\Feature;

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\PrincipalWelcome;
use App\Models\SiteSetting;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_published_principal_welcomes_and_hides_drafts(): void
    {
        PrincipalWelcome::query()->create([
            'title' => 'Sambutan Terbit',
            'slug' => 'sambutan-terbit',
            'category' => 'Kepala Sekolah',
            'summary' => 'Salam yang tampil.',
            'body' => 'Isi sambutan yang tampil.',
            'status' => 'published',
            'sort_order' => 1,
            'metadata' => [
                'name' => 'Kepala Sekolah Terbit',
                'description' => 'Deskripsi yang tampil.',
            ],
        ]);
        PrincipalWelcome::query()->create([
            'title' => 'Sambutan Draft',
            'slug' => 'sambutan-draft',
            'body' => 'Isi draft yang tidak boleh tampil.',
            'status' => 'draft',
            'sort_order' => 2,
            'metadata' => ['name' => 'Kepala Sekolah Draft'],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kepala Sekolah Terbit')
            ->assertSee('Isi sambutan yang tampil.')
            ->assertDontSee('Kepala Sekolah Draft')
            ->assertDontSee('Isi draft yang tidak boleh tampil.');
    }

    public function test_homepage_renders_all_published_teachers_beyond_six_records(): void
    {
        $teacherNames = [];

        for ($index = 1; $index <= 10; $index++) {
            $teacherName = "Tenaga Pendidik {$index}";
            $teacherNames[] = $teacherName;

            Teacher::query()->create([
                'name' => $teacherName,
                'slug' => "tenaga-pendidik-{$index}",
                'position' => "Jabatan {$index}",
                'status' => 'published',
                'sort_order' => $index,
            ]);
        }

        Teacher::query()->create([
            'name' => 'Tenaga Nonaktif',
            'slug' => 'tenaga-nonaktif',
            'position' => 'Jabatan nonaktif',
            'status' => 'draft',
            'sort_order' => 11,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();

        foreach ($teacherNames as $teacherName) {
            $response->assertSee($teacherName);
        }

        $response->assertDontSee('Tenaga Nonaktif');
    }

    public function test_homepage_hides_placeholder_profile_values_from_teacher_modal(): void
    {
        $placeholderTeacher = Teacher::query()->create([
            'name' => 'DATA CONTOH 03',
            'slug' => 'data-contoh-03',
            'position' => 'Jabatan Contoh 03',
            'category' => 'DATA CONTOH kategori',
            'summary' => 'DATA CONTOH - Ganti dengan deskripsi profil yang sebenarnya.',
            'education' => 'Jabatan Contoh pendidikan',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $actualTeacher = Teacher::query()->create([
            'name' => 'Guru Nyata',
            'slug' => 'guru-nyata',
            'position' => 'Guru Matematika',
            'category' => 'Guru',
            'summary' => 'Mengajar sejak 2015.',
            'education' => 'S1 Pendidikan Matematika',
            'status' => 'published',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $content = $response->getContent() ?? '';
        $profileMarkupFor = static function (string $content, Teacher $teacher): string {
            $start = strpos($content, 'id="teacher-profile-'.$teacher->getKey().'"');

            if ($start === false) {
                return '';
            }

            $end = strpos($content, '</article>', $start);

            if ($end === false) {
                return '';
            }

            return substr($content, $start, $end - $start);
        };
        $placeholderModal = $profileMarkupFor($content, $placeholderTeacher);
        $actualModal = $profileMarkupFor($content, $actualTeacher);

        $this->assertSame(true, str_contains(
            $placeholderModal,
            '<h2 id="teacher-profile-'.$placeholderTeacher->getKey().'-name">Profil Pendidik</h2>',
        ));
        $this->assertSame(false, str_contains($placeholderModal, 'DATA CONTOH'));
        $this->assertSame(false, str_contains($placeholderModal, 'Jabatan Contoh'));
        $this->assertSame(true, str_contains($actualModal, 'Guru Nyata'));
        $this->assertSame(true, str_contains($actualModal, 'Guru Matematika'));
        $this->assertSame(true, str_contains($actualModal, 'Guru'));
        $this->assertSame(true, str_contains($actualModal, 'Mengajar sejak 2015.'));
        $this->assertSame(true, str_contains($actualModal, 'S1 Pendidikan Matematika'));
    }

    public function test_homepage_renders_published_news_without_a_homepage_group(): void
    {
        $this->freezeTime();

        NewsArticle::query()->create([
            'title' => 'Berita Unggulan',
            'slug' => 'berita-unggulan',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'metadata' => ['homepage_group' => 'featured'],
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Terbaru Tanpa Grup',
            'slug' => 'berita-terbaru-tanpa-grup',
            'status' => 'published',
            'published_at' => now(),
            'metadata' => ['homepage_group' => 'null'],
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Lama Tanpa Metadata',
            'slug' => 'berita-lama-tanpa-metadata',
            'status' => 'published',
            'published_at' => now()->subHours(2),
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Arsip Lama',
            'slug' => 'berita-arsip-lama',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'metadata' => ['homepage_group' => 'archive'],
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Draft',
            'slug' => 'berita-draft',
            'status' => 'draft',
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Unggulan')
            ->assertSee('Berita Terbaru Tanpa Grup')
            ->assertSee('Berita Lama Tanpa Metadata')
            ->assertDontSee('Berita Arsip Lama')
            ->assertDontSee('Berita Draft');

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Arsip Lama');
    }

    public function test_homepage_shows_one_main_article_and_only_three_latest_articles(): void
    {
        $this->freezeTime();

        for ($index = 1; $index <= 16; $index++) {
            $placement = match ($index) {
                1 => 'featured',
                2 => 'carousel',
                3 => 'below_carousel',
                4 => 'sidebar',
                default => 'auto',
            };

            NewsArticle::query()->create([
                'title' => "Berita Beranda {$index}",
                'slug' => "berita-beranda-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'image_path' => "/images/berita-{$index}.jpg",
                'metadata' => ['homepage_group' => $placement],
            ]);
        }
        NewsArticle::query()->create([
            'title' => 'Berita Hanya di Halaman Berita',
            'slug' => 'berita-hanya-di-halaman-berita',
            'status' => 'published',
            'published_at' => now(),
            'metadata' => ['homepage_group' => 'news_only'],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('data-news-home-layout', false)
            ->assertSee('BERITA & INFORMASI')
            ->assertSee('Berita SMK Islamiyah Ciputat')
            ->assertSee('Informasi terbaru, kegiatan, prestasi, dan perkembangan sekolah.')
            ->assertSee('news-section-heading__visual', false)
            ->assertSee('news-section-heading__diagonal', false)
            ->assertSee('news-section-heading__wave', false)
            ->assertSee('bagorundtenagapendidik.png')
            ->assertSee('images/motivberita/garis.png')
            ->assertSee('images/motivberita/123.png')
            ->assertSee('images/motivberita/had.png')
            ->assertSee('data-news-track', false)
            ->assertSee('data-news-scroll="left"', false)
            ->assertSee('data-news-scroll="right"', false)
            ->assertSee('Berita Beranda 1')
            ->assertSee('Berita Beranda 2')
            ->assertSee('Berita Beranda 3')
            ->assertSee('Berita Beranda 4')
            ->assertDontSee('Berita Sekolah Lainnya')
            ->assertDontSee('Berita Populer')
            ->assertDontSee('Berita Hanya di Halaman Berita');

        $response->assertSee('Berita Beranda 5')
            ->assertSee('Berita Beranda 8')
            ->assertDontSee('Berita Beranda 9')
            ->assertDontSee('Berita Beranda 16');

        $content = $response->getContent() ?? '';

        $this->assertSame(4, substr_count($content, 'data-news-home-item'));
        $this->assertSame(1, substr_count($content, 'class="news-featured"'));
        $this->assertSame(3, substr_count($content, 'class="news-card"'));
        $this->assertSame(4, substr_count($content, 'data-news-below-carousel-item'));
        $this->assertSame(0, substr_count($content, 'data-news-sidebar-item'));

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Hanya di Halaman Berita');
    }

    public function test_homepage_keeps_an_explicitly_selected_older_main_article_visible(): void
    {
        $this->freezeTime();

        NewsArticle::query()->create([
            'title' => 'Berita Utama Pilihan Lama',
            'slug' => 'berita-utama-pilihan-lama',
            'status' => 'published',
            'published_at' => now()->subDays(30),
            'metadata' => ['homepage_group' => 'featured'],
        ]);

        for ($index = 1; $index <= 14; $index++) {
            NewsArticle::query()->create([
                'title' => "Berita Otomatis {$index}",
                'slug' => "berita-otomatis-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'metadata' => ['homepage_group' => 'auto'],
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Berita Utama Pilihan Lama');
        for ($index = 1; $index <= 3; $index++) {
            $response->assertSee("Berita Otomatis {$index}");
        }
        $response->assertSee('Berita Otomatis 4')
            ->assertSee('Berita Otomatis 7')
            ->assertDontSee('Berita Otomatis 8')
            ->assertDontSee('Berita Otomatis 14');

        $content = $response->getContent() ?? '';
        $this->assertSame(4, substr_count($content, 'data-news-home-item'));
        $this->assertSame(4, substr_count($content, 'data-news-below-carousel-item'));
        $this->assertSame(0, substr_count($content, 'data-news-sidebar-item'));
    }

    public function test_homepage_excludes_archive_articles_and_orders_the_latest_news_automatically(): void
    {
        for ($index = 1; $index <= 4; $index++) {
            NewsArticle::query()->create([
                'title' => "Berita Terbaru {$index}",
                'slug' => "berita-terbaru-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'metadata' => ['homepage_group' => 'latest'],
            ]);
        }
        NewsArticle::query()->create([
            'title' => 'Berita Lampau Tidak di Beranda',
            'slug' => 'berita-lampau-tidak-di-beranda',
            'status' => 'published',
            'published_at' => now(),
            'metadata' => ['homepage_group' => 'archive'],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Berita Terbaru 1')
            ->assertSee('Berita Terbaru 2')
            ->assertSee('Berita Terbaru 3')
            ->assertSee('Berita Terbaru 4')
            ->assertDontSee('Berita Lampau Tidak di Beranda');
        $content = $response->getContent() ?? '';
        $this->assertSame(4, substr_count($content, 'data-news-home-item'));

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Lampau Tidak di Beranda');
    }

    public function test_homepage_uses_the_most_recent_article_as_main_fallback_and_shows_three_more(): void
    {
        for ($index = 1; $index <= 4; $index++) {
            NewsArticle::query()->create([
                'title' => "Populer Terbaru {$index}",
                'slug' => "populer-terbaru-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'metadata' => ['homepage_group' => 'auto'],
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk();
        $content = $response->getContent() ?? '';
        $this->assertSame(4, substr_count($content, 'data-news-home-item'));
        $this->assertSame(1, substr_count($content, 'data-news-featured-slide'));
        $response->assertSee('Populer Terbaru 1')
            ->assertSee('Populer Terbaru 2')
            ->assertSee('Populer Terbaru 3')
            ->assertSee('Populer Terbaru 4');
    }

    public function test_homepage_links_to_configured_videos_from_the_latest_activity(): void
    {
        NewsArticle::query()->create([
            'title' => 'Kegiatan Robotika',
            'slug' => 'kegiatan-robotika',
            'summary' => 'Kegiatan pembelajaran siswa.',
            'image_path' => '/images/5.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        GalleryPhoto::query()->create([
            'title' => 'Kegiatan Robotika',
            'slug' => 'kegiatan-robotika',
            'summary' => 'Kegiatan pembelajaran siswa.',
            'image_path' => '/images/5.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        foreach ([
            'home.news.video_1' => 'https://www.youtube.com/watch?v=AbCdeFGhi12',
            'home.news.video_2' => 'https://cdn.example.test/videos/kegiatan-sekolah.mp4',
        ] as $key => $value) {
            SiteSetting::query()->create([
                'key' => $key,
                'group' => 'home',
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Kegiatan Robotika');
    }
}
