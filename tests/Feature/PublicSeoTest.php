<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_emits_route_specific_social_metadata_and_school_schema(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('about').'">', false)
            ->assertSee('<meta property="og:url" content="'.route('about').'">', false)
            ->assertSee('"@type":"EducationalOrganization"', false);
    }

    public function test_footer_uses_semantic_navigation_and_social_links(): void
    {
        foreach ([
            'identity.whatsapp' => '+6281234567890',
            'identity.email' => 'info@smkislamiyahciputat.sch.id',
            'identity.instagram' => 'https://instagram.com/smkislamiyahciputat',
            'identity.facebook' => 'https://facebook.com/smkislamiyahciputat',
            'identity.twitter' => 'https://x.com/smkislamiyahciputat',
            'identity.youtube' => 'https://youtube.com/@smkislamiyahciputat',
        ] as $key => $value) {
            SiteSetting::query()->create([
                'key' => $key,
                'group' => explode('.', $key)[0],
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('aria-label="Navigasi utama footer"', false)
            ->assertSee('aria-label="Program keahlian footer"', false)
            ->assertSee('aria-label="Kontak dan media sosial sekolah"', false)
            ->assertSee('aria-label="WhatsApp"', false)
            ->assertSee('aria-label="Email sekolah"', false)
            ->assertSee('aria-label="Instagram"', false)
            ->assertSee('aria-label="Facebook"', false)
            ->assertSee('aria-label="X / Twitter"', false)
            ->assertSee('aria-label="YouTube"', false)
            ->assertDontSee('Kebijakan Privasi', false);
    }

    public function test_about_page_shows_vision_mission_and_gallery_empty_states_when_content_is_missing(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('Visi Sekolah')
            ->assertSee('Informasi visi sekolah belum tersedia.')
            ->assertSee('Misi Sekolah')
            ->assertSee('Informasi misi sekolah belum tersedia.')
            ->assertDontSee('Profil Lengkap')
            ->assertDontSee('aria-label="Breadcrumb"', false)
            ->assertDontSee('class="about-hero"', false)
            ->assertSee('Galeri Sekolah')
            ->assertSee('Belum ada foto galeri sekolah yang dipublikasikan.')
            ->assertDontSee('Perjalanan Sekolah');
    }

    public function test_about_page_omits_hero_and_keeps_remaining_sections(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertDontSee('class="about-hero"', false)
            ->assertSee('class="about-profile"', false)
            ->assertDontSee('class="about-profile__meta"', false)
            ->assertSee('Visi Sekolah')
            ->assertSee('Lokasi Sekolah')
            ->assertSee('data-lucide="target"', false)
            ->assertSee('data-lucide="list-checks"', false)
            ->assertSee('data-lucide="map-pin"', false)
            ->assertSee('data-lucide="map-pinned"', false)
            ->assertSee('data-lucide="external-link"', false)
            ->assertSee('src="https://www.google.com/maps?q=', false);
    }

    public function test_about_page_renders_school_identity_and_statistics_from_existing_data(): void
    {
        foreach ([
            'about.title' => 'Tentang SMK Islamiyah Ciputat',
            'about.description' => 'Profil resmi sekolah dari pengaturan admin.',
            'about.image' => '/images/10.jpeg',
            'about.vision' => 'Menjadi sekolah kejuruan yang unggul.',
            'about.mission' => "Menyelenggarakan pendidikan berkualitas.\nMembentuk karakter peserta didik.",
            'home.about.image_alt' => 'Siswa dan guru SMK Islamiyah Ciputat',
            'home.about.paragraph_1' => 'SMK Islamiyah Ciputat mendampingi siswa berkembang.',
            'home.stats.student_count' => '1.000',
            'home.stats.teacher_count' => '10',
            'identity.location' => 'Ciputat, Tangerang Selatan',
        ] as $key => $value) {
            SiteSetting::query()->create([
                'key' => $key,
                'group' => explode('.', $key)[0],
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
            ]);
        }

        Program::query()->create([
            'title' => 'Program Keahlian Aktif',
            'slug' => 'program-keahlian-aktif-about',
            'status' => 'published',
        ]);

        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('Tentang SMK Islamiyah Ciputat')
            ->assertSee('Profil resmi sekolah dari pengaturan admin.')
            ->assertSee('class="about-profile"', false)
            ->assertSee('/images/10.jpeg')
            ->assertSee('Siswa dan guru SMK Islamiyah Ciputat')
            ->assertSee('Menjadi sekolah kejuruan yang unggul.')
            ->assertSee('Menyelenggarakan pendidikan berkualitas.')
            ->assertSee('Membentuk karakter peserta didik.')
            ->assertSee('Ciputat, Tangerang Selatan')
            ->assertSee('1.000')
            ->assertSee('Program Keahlian')
            ->assertSee('Tenaga Pendidik')
            ->assertDontSee('Tahun Berdiri')
            ->assertDontSee('Akreditasi');
    }

    public function test_news_page_emits_article_metadata_and_structured_data(): void
    {
        $article = NewsArticle::query()->create([
            'title' => 'Berita Sekolah',
            'slug' => 'berita-sekolah-seo',
            'summary' => 'Ringkasan berita untuk hasil pencarian.',
            'body' => 'Isi berita.',
            'status' => 'published',
            'published_at' => now(),
            'meta_title' => 'Judul SEO Berita Sekolah',
            'meta_description' => 'Deskripsi SEO berita sekolah.',
            'image_path' => 'news/berita-sekolah.webp',
        ]);

        $response = $this->get(route('news.show', $article->slug));

        $response->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:title" content="Judul SEO Berita Sekolah">', false)
            ->assertSee('"@type":"NewsArticle"', false)
            ->assertSee('berita-sekolah.webp', false);
    }

    public function test_sitemap_includes_public_routes_and_published_content_only(): void
    {
        $publishedProgram = Program::query()->create([
            'title' => 'Program Aktif',
            'slug' => 'program-aktif-seo',
            'status' => 'published',
        ]);
        $publishedNews = NewsArticle::query()->create([
            'title' => 'Berita Aktif',
            'slug' => 'berita-aktif-seo',
            'status' => 'published',
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Draft',
            'slug' => 'berita-draft-seo',
            'status' => 'draft',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('program.show', $publishedProgram->slug), false)
            ->assertSee(route('news.show', $publishedNews->slug), false)
            ->assertDontSee(route('news.show', 'berita-draft-seo'), false);
    }

    public function test_robots_file_blocks_admin_and_lists_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertSee('Disallow: /adminsmkiciputat/')
            ->assertSee('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml');
    }

    public function test_ga_and_search_console_metadata_are_loaded_when_configured(): void
    {
        putenv('GOOGLE_SITE_VERIFICATION=example-verification');
        putenv('GA_MEASUREMENT_ID=G-TEST123');

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('meta name="google-site-verification" content="example-verification"', false)
            ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123', false)
            ->assertSee("gtag('config', 'G-TEST123')", false);

        putenv('GOOGLE_SITE_VERIFICATION');
        putenv('GA_MEASUREMENT_ID');
    }
}
