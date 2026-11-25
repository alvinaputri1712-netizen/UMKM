<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\PrincipalWelcome;
use App\Models\SiteSetting;
use App\Models\Teacher;
use App\Support\MediaUsage;
use App\Support\SiteConfiguration;
use Database\Seeders\CmsContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CmsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_require_authentication(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.register.success'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_sign_in_and_sign_out(): void
    {
        $admin = $this->createAdmin();

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'Secure-password-1234',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_first_superadmin_can_register_once_in_testing_and_is_signed_in(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertViewIs('admin.setup-welcome')
            ->assertSee(route('admin.register.form'));

        $this->get(route('admin.register.form'))
            ->assertOk()
            ->assertViewIs('admin.register')
            ->assertSee('Buat Administrator Utama');

        $this->post(route('admin.register.store'), [
            'name' => 'Superadmin Testing',
            'username' => 'superadmintesting',
            'email' => 'superadmin@example.test',
            'password' => 'Secure-password-1234',
            'password_confirmation' => 'Secure-password-1234',
        ])->assertRedirect(route('admin.register.success'))
            ->assertSessionHas('success', 'Akun Superadmin pertama berhasil dibuat.');

        $this->assertDatabaseHas('admins', [
            'username' => 'superadmintesting',
            'role' => 'superadmin',
        ]);
        $this->assertAuthenticated('admin');

        $this->get(route('admin.register.success'))
            ->assertOk()
            ->assertViewIs('admin.register-success')
            ->assertSee('Akun Superadmin')
            ->assertSee(route('admin.dashboard'));

        $this->get(route('admin.register'))
            ->assertNotFound();
        $this->get(route('admin.register.form'))
            ->assertNotFound();

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee(route('admin.register'));
    }

    public function test_first_superadmin_registration_is_disabled_outside_local_and_testing(): void
    {
        config(['app.env' => 'production']);

        $this->get(route('admin.register'))
            ->assertNotFound();
        $this->get(route('admin.register.form'))
            ->assertNotFound();

        $this->post(route('admin.register.store'), [
            'name' => 'Superadmin Production',
            'username' => 'superadminproduction',
            'password' => 'Secure-password-1234',
            'password_confirmation' => 'Secure-password-1234',
        ])->assertNotFound();

        $this->assertDatabaseMissing('admins', [
            'username' => 'superadminproduction',
        ]);
    }

    public function test_first_superadmin_registration_is_disabled_when_any_admin_account_already_exists(): void
    {
        $this->createAdmin('admin');

        $this->get(route('admin.register'))
            ->assertNotFound();

        $this->post(route('admin.register.store'), [
            'name' => 'Superadmin Kedua',
            'username' => 'superadminkedua',
            'password' => 'Secure-password-1234',
            'password_confirmation' => 'Secure-password-1234',
        ])->assertNotFound();

        $this->assertDatabaseMissing('admins', [
            'username' => 'superadminkedua',
        ]);
    }

    public function test_admin_can_publish_a_program_that_appears_on_public_pages(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'programs'), [
                'title' => 'Program Uji',
                'slug' => 'program-uji',
                'summary' => 'Deskripsi program uji.',
                'body' => 'Deskripsi detail program uji.',
                'status' => 'published',
                'metadata' => [
                    'abbreviation' => 'PU',
                    'route_key' => 'program-uji',
                    'icon' => 'graduation-cap',
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'programs'));

        $this->assertDatabaseHas('programs', [
            'slug' => 'program-uji',
            'status' => 'published',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Program Uji');

        $this->get(route('program.show', 'program-uji'))
            ->assertOk()
            ->assertSee('Deskripsi detail program uji.')
            ->assertDontSee('Deskripsi program uji.');
    }

    public function test_admin_can_publish_a_job_vacancy_to_the_dedicated_table(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'jobs'), [
                'title' => 'Staf Administrasi Uji',
                'slug' => 'staf-administrasi-uji',
                'summary' => 'Lowongan uji untuk memastikan penyimpanan BKK.',
                'status' => 'published',
                'metadata' => [
                    'company' => 'Perusahaan Uji',
                    'position' => 'Staf Administrasi',
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'jobs'));

        $this->assertDatabaseHas('job_vacancies', [
            'slug' => 'staf-administrasi-uji',
            'status' => 'published',
        ]);

        $this->get(route('bkk.jobs'))
            ->assertOk()
            ->assertSee('Staf Administrasi')
            ->assertSee('Perusahaan Uji');
    }

    public function test_admin_news_form_shows_three_placements_without_manual_order_or_seo_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.create', 'news'))
            ->assertOk()
            ->assertSee('Penempatan berita')
            ->assertSee('Berita utama (1 slot)')
            ->assertSee('Berita terbaru (maks. 3 di Beranda)')
            ->assertSee('Berita lampau (arsip)')
            ->assertSee('Tiga berita terbaru di Beranda dipilih otomatis berdasarkan tanggal publikasi')
            ->assertSee('Tambahkan maksimal 20 foto atau video')
            ->assertSee('File dari perangkat')
            ->assertDontSee('File Manager')
            ->assertDontSee('Urutan tampil')
            ->assertDontSee('Pengaturan SEO')
            ->assertDontSee('Tandai sebagai unggulan');
    }

    public function test_admin_selecting_a_main_news_article_replaces_the_existing_main_slot(): void
    {
        $admin = $this->createAdmin();
        $previousMain = NewsArticle::query()->create([
            'title' => 'Berita Utama Sebelumnya',
            'slug' => 'berita-utama-sebelumnya',
            'status' => 'published',
            'metadata' => ['homepage_group' => 'featured'],
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Utama Baru',
                'slug' => 'berita-utama-baru',
                'status' => 'published',
                'metadata' => ['homepage_group' => 'featured'],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $newMain = NewsArticle::query()->where('slug', 'berita-utama-baru')->firstOrFail();
        $this->assertSame('featured', $newMain->metadata['homepage_group']);
        $this->assertSame('latest', $previousMain->fresh()->metadata['homepage_group']);
        $this->assertDatabaseHas('content_activities', [
            'content_type' => 'news',
            'content_id' => $previousMain->id,
            'action' => 'updated',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.index', 'news'))
            ->assertOk()
            ->assertSee('Berita utama (1 slot)')
            ->assertSee('Berita terbaru (maks. 3 di Beranda)');

        $homepageContent = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Utama Baru')
            ->assertSee('Berita Utama Sebelumnya')
            ->getContent() ?? '';

        $this->assertSame(1, substr_count($homepageContent, 'data-news-featured-slide'));
    }

    public function test_admin_promoting_an_existing_article_replaces_the_current_main(): void
    {
        $admin = $this->createAdmin();
        $previousMain = NewsArticle::query()->create([
            'title' => 'Berita Utama Lama',
            'slug' => 'berita-utama-lama',
            'status' => 'published',
            'metadata' => ['homepage_group' => 'featured'],
        ]);
        $article = NewsArticle::query()->create([
            'title' => 'Artikel yang Dipromosikan',
            'slug' => 'artikel-yang-dipromosikan',
            'status' => 'published',
            'metadata' => ['homepage_group' => 'latest'],
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', ['type' => 'news', 'entry' => $article]), [
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => 'published',
                'metadata' => ['homepage_group' => 'featured'],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertSame('featured', $article->fresh()->metadata['homepage_group']);
        $this->assertSame('latest', $previousMain->fresh()->metadata['homepage_group']);
    }

    public function test_admin_saving_a_draft_as_main_does_not_replace_the_published_main(): void
    {
        $admin = $this->createAdmin();
        $publishedMain = NewsArticle::query()->create([
            'title' => 'Berita Utama Aktif',
            'slug' => 'berita-utama-aktif',
            'status' => 'published',
            'metadata' => ['homepage_group' => 'featured'],
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Utama Draft',
                'slug' => 'berita-utama-draft',
                'status' => 'draft',
                'metadata' => ['homepage_group' => 'featured'],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertSame('featured', $publishedMain->fresh()->metadata['homepage_group']);
        $this->assertDatabaseHas('news_articles', [
            'slug' => 'berita-utama-draft',
            'status' => 'draft',
        ]);

        $this->get(route('admin.content.index', 'news'))
            ->assertOk()
            ->assertSee('Berita utama (1 slot) (Draft)');
    }

    public function test_admin_can_publish_news_with_device_files_and_urls_to_database_and_public_pages(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Dengan Banyak Media',
                'slug' => 'berita-dengan-banyak-media',
                'summary' => 'Berita dengan foto dan beberapa video.',
                'body' => 'Isi berita.',
                'status' => 'published',
                'metadata' => ['homepage_group' => 'latest'],
                'news_media' => [
                    [
                        'type' => 'image',
                        'source' => 'device',
                        'file' => UploadedFile::fake()->image('news-cover.jpg', 600, 400),
                    ],
                    [
                        'type' => 'video',
                        'source' => 'device',
                        'file' => UploadedFile::fake()->create('news-clip.mp4', 100, 'video/mp4'),
                    ],
                    ['type' => 'video', 'source' => 'url', 'url' => 'https://www.youtube.com/watch?v=AbCdeFGhi12'],
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $article = NewsArticle::query()->where('slug', 'berita-dengan-banyak-media')->firstOrFail();
        $this->assertDatabaseHas('news_articles', [
            'id' => $article->id,
            'status' => 'published',
        ]);
        $this->assertCount(3, $article->metadata['media']);
        $imageAsset = MediaAsset::query()->findOrFail($article->metadata['media'][0]['asset_id']);
        $videoAsset = MediaAsset::query()->findOrFail($article->metadata['media'][1]['asset_id']);
        $this->assertSame($imageAsset->publicPath(), $article->image_path);
        $this->assertSame('image', $imageAsset->media_type);
        $this->assertSame('video', $videoAsset->media_type);
        Storage::disk('public')->assertExists($imageAsset->publicPath());
        Storage::disk('public')->assertExists($videoAsset->publicPath());

        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.index', 'news'))
            ->assertOk()
            ->assertSee('Berita Dengan Banyak Media');
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Dengan Banyak Media');
        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Dengan Banyak Media');
        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/AbCdeFGhi12', false)
            ->assertSee($videoAsset->publicPath(), false);
    }

    public function test_news_media_is_cleaned_up_when_the_content_record_is_not_saved(): void
    {
        Storage::fake('public');
        NewsArticle::creating(fn (NewsArticle $article): bool => false);
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Gagal Disimpan',
                'slug' => 'berita-gagal-disimpan',
                'summary' => 'Ringkasan.',
                'body' => 'Isi berita.',
                'status' => 'published',
                'metadata' => ['homepage_group' => 'latest'],
                'news_media' => [[
                    'type' => 'video',
                    'source' => 'device',
                    'file' => UploadedFile::fake()->create('unsaved-news.mp4', 100, 'video/mp4'),
                ]],
            ])
            ->assertStatus(500);

        $this->assertDatabaseCount('news_articles', 0);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('cms/media/videos'));
    }

    public function test_admin_rejects_more_than_twenty_news_media_items_and_non_http_media_urls(): void
    {
        $admin = $this->createAdmin();
        $tooManyMediaItems = collect(range(1, 21))
            ->map(fn (int $index): array => [
                'type' => 'image',
                'source' => 'url',
                'url' => 'https://example.test/news-'.$index.'.jpg',
            ])
            ->all();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Dengan Media Terlalu Banyak',
                'slug' => 'berita-dengan-media-terlalu-banyak',
                'status' => 'published',
                'metadata' => ['homepage_group' => 'latest'],
                'news_media' => $tooManyMediaItems,
            ])
            ->assertSessionHasErrors('news_media');

        $this->post(route('admin.content.store', 'news'), [
            'title' => 'Berita Dengan URL Tidak Valid',
            'slug' => 'berita-dengan-url-tidak-valid',
            'status' => 'published',
            'metadata' => ['homepage_group' => 'latest'],
            'news_media' => [
                ['type' => 'video', 'source' => 'url', 'url' => 'javascript:alert(1)'],
            ],
        ])->assertSessionHasErrors('news_media.0.url');
    }

    public function test_admin_scheduling_a_main_news_article_keeps_the_current_main_until_its_date(): void
    {
        $this->freezeTime();
        $admin = $this->createAdmin();
        $publishedMain = NewsArticle::query()->create([
            'title' => 'Berita Utama Saat Ini',
            'slug' => 'berita-utama-saat-ini',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'metadata' => ['homepage_group' => 'featured'],
        ]);
        $scheduledAt = now()->addDay();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Utama Terjadwal',
                'slug' => 'berita-utama-terjadwal',
                'status' => 'published',
                'published_at' => $scheduledAt->format('Y-m-d\TH:i'),
                'metadata' => ['homepage_group' => 'featured'],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertSame('featured', $publishedMain->fresh()->metadata['homepage_group']);
        $this->assertDatabaseHas('news_articles', [
            'slug' => 'berita-utama-terjadwal',
            'status' => 'published',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Utama Saat Ini')
            ->assertDontSee('Berita Utama Terjadwal');

        $this->get(route('admin.content.index', 'news'))
            ->assertOk()
            ->assertSee('Berita utama (1 slot) (Terjadwal)');
    }

    public function test_admin_news_crud_stays_synchronized_across_home_portal_and_detail(): void
    {
        $this->freezeTime();
        Storage::fake('public');
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Sinkron',
                'slug' => 'berita-sinkron',
                'summary' => 'Ringkasan berita awal.',
                'body' => 'Isi berita awal.',
                'category' => 'Kegiatan Sekolah',
                'status' => 'published',
                'published_at' => now()->format('Y-m-d\TH:i'),
                'metadata' => ['homepage_group' => 'latest'],
                'news_media' => [
                    ['type' => 'image', 'source' => 'url', 'url' => 'https://example.test/berita-awal.jpg'],
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $article = NewsArticle::query()->where('slug', 'berita-sinkron')->firstOrFail();
        $oldImagePath = $article->image_path;

        $article->update([
            'metadata' => [
                'homepage_group' => 'latest',
                'author' => 'Redaksi Sekolah',
                'images' => ['/images/galeri-berita.jpg'],
            ],
        ]);

        $this->assertSame(1, NewsArticle::query()->count());
        $this->assertDatabaseMissing('content_entries', [
            'type' => 'news',
            'slug' => $article->slug,
        ]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.edit', ['type' => 'news', 'entry' => $article]))
            ->assertOk()
            ->assertSee($oldImagePath, false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Sinkron')
            ->assertSee('Ringkasan berita awal.')
            ->assertSee(route('news.show', $article->slug), false)
            ->assertSee($oldImagePath, false);
        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Sinkron')
            ->assertSee('Kegiatan Sekolah')
            ->assertSee($oldImagePath, false);
        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('Isi berita awal.')
            ->assertSee('Redaksi Sekolah')
            ->assertSee('/images/galeri-berita.jpg', false)
            ->assertSee($oldImagePath, false);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', ['type' => 'news', 'entry' => $article]), [
                'title' => 'Berita Sinkron Diperbarui',
                'slug' => $article->slug,
                'summary' => 'Ringkasan berita terbaru.',
                'body' => 'Isi berita terbaru.',
                'category' => 'Prestasi',
                'status' => 'draft',
                'published_at' => now()->format('Y-m-d\TH:i'),
                'metadata' => ['homepage_group' => 'latest'],
                'news_media' => [
                    ['type' => 'image', 'source' => 'url', 'url' => 'https://example.test/berita-terbaru.jpg'],
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $article->refresh();
        $draftImagePath = $article->image_path;
        $this->assertSame('Redaksi Sekolah', $article->metadata['author']);
        $this->assertSame(['/images/galeri-berita.jpg'], $article->metadata['images']);
        $this->assertSame('Prestasi', $article->category);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.edit', ['type' => 'news', 'entry' => $article]))
            ->assertOk()
            ->assertSee($draftImagePath, false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Berita Sinkron Diperbarui');
        $this->get(route('news.index'))
            ->assertOk()
            ->assertDontSee('Berita Sinkron Diperbarui');
        $this->get(route('news.show', $article->slug))
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', ['type' => 'news', 'entry' => $article]), [
                'title' => $article->title,
                'slug' => $article->slug,
                'summary' => $article->summary,
                'body' => $article->body,
                'category' => $article->category,
                'status' => 'published',
                'published_at' => now()->format('Y-m-d\TH:i'),
                'metadata' => ['homepage_group' => 'latest'],
                'news_media' => [
                    ['type' => 'image', 'source' => 'url', 'url' => $draftImagePath],
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Sinkron Diperbarui')
            ->assertSee('Ringkasan berita terbaru.')
            ->assertSee(route('news.show', $article->slug), false)
            ->assertSee($draftImagePath, false);
        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Sinkron Diperbarui')
            ->assertSee('Prestasi')
            ->assertSee($draftImagePath, false);
        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('Isi berita terbaru.')
            ->assertSee('Redaksi Sekolah')
            ->assertSee('/images/galeri-berita.jpg', false)
            ->assertSee($draftImagePath, false);
        $this->get(route('news.show', $article->slug))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.content.destroy', ['type' => 'news', 'entry' => $article]))
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertDatabaseMissing('news_articles', ['id' => $article->id]);
        $this->assertDatabaseHas('content_activities', [
            'content_type' => 'news',
            'content_id' => $article->id,
            'action' => 'deleted',
        ]);
        $this->get(route('home'))->assertOk()->assertDontSee('Berita Sinkron Diperbarui');
        $this->get(route('news.index'))->assertOk()->assertDontSee('Berita Sinkron Diperbarui');
        $this->get(route('news.show', 'berita-sinkron'))->assertNotFound();
    }

    public function test_deleting_news_preserves_a_photo_still_used_by_another_article(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/news/shared.jpg', 'image-data');
        $admin = $this->createAdmin();
        $firstArticle = NewsArticle::query()->create([
            'title' => 'Berita Foto Bersama Pertama',
            'slug' => 'berita-foto-bersama-pertama',
            'image_path' => 'cms/news/shared.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $secondArticle = NewsArticle::query()->create([
            'title' => 'Berita Foto Bersama Kedua',
            'slug' => 'berita-foto-bersama-kedua',
            'image_path' => 'cms/news/shared.jpg',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.content.destroy', ['type' => 'news', 'entry' => $firstArticle]))
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertDatabaseMissing('news_articles', ['id' => $firstArticle->id]);
        $this->assertDatabaseHas('news_articles', ['id' => $secondArticle->id]);
        Storage::disk('public')->assertExists('cms/news/shared.jpg');

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.content.destroy', ['type' => 'news', 'entry' => $secondArticle]))
            ->assertRedirect(route('admin.content.index', 'news'));

        Storage::disk('public')->assertMissing('cms/news/shared.jpg');
    }

    public function test_editing_news_preserves_unchanged_media_and_replaces_unused_photo_and_video(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $oldImage = $this->createMediaAsset('image', 'cms/media/originals/old-news.jpg', 'image/jpeg');
        $oldVideo = $this->createMediaAsset('video', 'cms/media/videos/old-news.mp4', 'video/mp4');
        $article = NewsArticle::query()->create([
            'title' => 'Berita Media Lama',
            'slug' => 'berita-media-lama',
            'summary' => 'Ringkasan awal.',
            'body' => 'Isi awal.',
            'status' => 'published',
            'published_at' => now(),
            'image_path' => $oldImage->publicPath(),
            'metadata' => [
                'homepage_group' => 'latest',
                'media' => [
                    ['type' => 'image', 'source' => 'asset', 'asset_id' => $oldImage->id, 'path' => $oldImage->publicPath()],
                    ['type' => 'video', 'source' => 'asset', 'asset_id' => $oldVideo->id, 'path' => $oldVideo->publicPath()],
                ],
            ],
        ]);
        $unchangedData = [
            'title' => 'Berita Media Lama Diperbarui',
            'slug' => 'berita-media-lama',
            'summary' => 'Ringkasan diperbarui.',
            'body' => 'Isi diperbarui.',
            'category' => 'Kegiatan Sekolah',
            'status' => 'published',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'metadata' => ['homepage_group' => 'latest'],
            'news_media' => [
                ['type' => 'image', 'source' => 'asset', 'asset_id' => $oldImage->id],
                ['type' => 'video', 'source' => 'asset', 'asset_id' => $oldVideo->id],
            ],
        ];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', ['type' => 'news', 'entry' => $article]), $unchangedData)
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertModelExists($oldImage->fresh());
        $this->assertModelExists($oldVideo->fresh());
        Storage::disk('public')->assertExists($oldImage->publicPath());
        Storage::disk('public')->assertExists($oldVideo->publicPath());

        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', ['type' => 'news', 'entry' => $article]), [
                ...$unchangedData,
                'news_media' => [
                    [
                        'type' => 'image',
                        'source' => 'device',
                        'file' => UploadedFile::fake()->image('news-new.jpg', 600, 400),
                    ],
                    [
                        'type' => 'video',
                        'source' => 'device',
                        'file' => UploadedFile::fake()->create('news-new.mp4', 100, 'video/mp4'),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.content.index', 'news'));

        $article->refresh();
        $newImage = MediaAsset::query()->where('filename', 'news-new.jpg')->firstOrFail();
        $newVideo = MediaAsset::query()->where('filename', 'news-new.mp4')->firstOrFail();
        $this->assertDatabaseMissing('media_assets', ['id' => $oldImage->id]);
        $this->assertDatabaseMissing('media_assets', ['id' => $oldVideo->id]);
        Storage::disk('public')->assertMissing($oldImage->publicPath());
        Storage::disk('public')->assertMissing($oldVideo->publicPath());
        Storage::disk('public')->assertExists($newImage->publicPath());
        Storage::disk('public')->assertExists($newVideo->publicPath());
        $this->assertSame($newImage->publicPath(), $article->image_path);
        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee($newVideo->publicPath(), false);
    }

    public function test_deleting_news_removes_unused_video_and_poster_assets(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $video = $this->createMediaAsset('video', 'cms/media/videos/news-to-delete.mp4', 'video/mp4');
        $posterPath = 'cms/media/posters/news-to-delete.webp';
        Storage::disk('public')->put($posterPath, 'poster-data');
        $video->update(['poster_path' => $posterPath]);
        $article = NewsArticle::query()->create([
            'title' => 'Berita Video Dihapus',
            'slug' => 'berita-video-dihapus',
            'status' => 'published',
            'published_at' => now(),
            'metadata' => [
                'media' => [[
                    'type' => 'video',
                    'source' => 'asset',
                    'asset_id' => $video->id,
                    'path' => $video->publicPath(),
                    'poster_path' => $posterPath,
                ]],
            ],
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.content.destroy', ['type' => 'news', 'entry' => $article]))
            ->assertRedirect(route('admin.content.index', 'news'));

        $this->assertDatabaseMissing('news_articles', ['id' => $article->id]);
        $this->assertDatabaseMissing('media_assets', ['id' => $video->id]);
        Storage::disk('public')->assertMissing($video->publicPath());
        Storage::disk('public')->assertMissing($posterPath);
        $this->get(route('news.show', $article->slug))->assertNotFound();
    }

    public function test_admin_rejects_an_unknown_homepage_placement(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'news'), [
                'title' => 'Berita Tanpa Posisi Valid',
                'slug' => 'berita-tanpa-posisi-valid',
                'status' => 'published',
                'metadata' => ['homepage_group' => 'unknown'],
            ])
            ->assertSessionHasErrors('metadata.homepage_group');

        $this->assertDatabaseMissing('news_articles', [
            'slug' => 'berita-tanpa-posisi-valid',
        ]);
    }

    public function test_admin_cannot_save_content_with_published_at_outside_mysql_timestamp_range(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', 'principal-welcomes'), [
                'title' => 'Sambutan Tahun 2026',
                'status' => 'published',
                'published_at' => '0026-12-12T12:11',
            ])
            ->assertSessionHasErrors('published_at');

        $this->assertDatabaseMissing('principal_welcomes', [
            'slug' => 'sambutan-tahun-2026',
        ]);
    }

    public function test_admin_can_manage_principal_welcomes_separately_from_teachers(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $type = 'principal-welcomes';

        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.index', $type))
            ->assertOk()
            ->assertSee(route('admin.content.index', $type));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.create', $type))
            ->assertOk()
            ->assertSee('Judul sambutan')
            ->assertSee('Jabatan')
            ->assertSee('Salam pembuka')
            ->assertSee('Status publikasi')
            ->assertDontSee('Slug URL')
            ->assertDontSee('Urutan tampil')
            ->assertDontSee('Tanggal publikasi')
            ->assertDontSee('Tandai sebagai unggulan')
            ->assertDontSee('Pengaturan SEO');

        PrincipalWelcome::query()->create([
            'title' => 'Sambutan Sebelumnya',
            'slug' => 'sambutan-sebelumnya-untuk-urutan',
            'status' => 'draft',
            'sort_order' => 7,
            'metadata' => [],
        ]);
        $publishedAt = now()->startOfMinute();
        $this->travelTo($publishedAt);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', $type), [
                'title' => 'Sambutan Kepala Sekolah 2026',
                'category' => 'Kepala SMK Islamiyah Ciputat',
                'summary' => 'Assalamu’alaikum warahmatullahi wabarakatuh.',
                'body' => 'Kami mendampingi siswa untuk berkembang.',
                'status' => 'published',
                'metadata' => [
                    'name' => 'Kepala Sekolah Uji',
                    'description' => 'Sambutan untuk peserta didik dan orang tua.',
                ],
                'image' => UploadedFile::fake()->image('kepala-sekolah.jpg', 600, 400),
            ])
            ->assertRedirect(route('admin.content.index', $type));

        $entry = PrincipalWelcome::query()
            ->where('slug', 'sambutan-kepala-sekolah-2026')
            ->firstOrFail();
        $imagePath = $entry->image_path;

        $this->assertSame(8, $entry->sort_order);
        $this->assertSame($publishedAt->format('Y-m-d H:i:s'), $entry->published_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('content_activities', [
            'content_type' => 'principal_welcome',
            'content_id' => $entry->id,
            'action' => 'created',
        ]);
        Storage::disk('public')->assertExists($imagePath);
        $this->assertDatabaseMissing('teachers', ['slug' => 'sambutan-kepala-sekolah-2026']);

        $this->travelTo($publishedAt->copy()->addMinute());
        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', ['type' => $type, 'entry' => $entry]), [
                'title' => 'Sambutan Kepala Sekolah 2027',
                'category' => 'Kepala SMK Islamiyah Ciputat',
                'summary' => 'Salam pembuka yang diperbarui.',
                'body' => 'Isi sambutan yang diperbarui.',
                'status' => 'published',
                'metadata' => [
                    'name' => 'Kepala Sekolah Uji',
                    'description' => 'Deskripsi yang diperbarui.',
                ],
            ])
            ->assertRedirect(route('admin.content.index', $type));

        $entry->refresh();
        $this->assertSame('sambutan-kepala-sekolah-2027', $entry->slug);
        $this->assertSame(8, $entry->sort_order);
        $this->assertSame($publishedAt->format('Y-m-d H:i:s'), $entry->published_at->format('Y-m-d H:i:s'));
        $this->assertSame($imagePath, $entry->image_path);
        $this->assertDatabaseHas('content_activities', [
            'content_type' => 'principal_welcome',
            'content_id' => $entry->id,
            'action' => 'updated',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kepala Sekolah Uji')
            ->assertSee('Isi sambutan yang diperbarui.');

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.content.destroy', ['type' => $type, 'entry' => $entry]))
            ->assertRedirect(route('admin.content.index', $type));

        $this->assertDatabaseMissing('principal_welcomes', ['id' => $entry->id]);
        $this->assertDatabaseHas('content_activities', [
            'content_type' => 'principal_welcome',
            'content_id' => $entry->id,
            'action' => 'deleted',
        ]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_principal_welcome_generates_unique_slugs_and_automatic_order(): void
    {
        $admin = $this->createAdmin();
        $type = 'principal-welcomes';

        PrincipalWelcome::query()->create([
            'title' => 'Sambutan Sebelumnya',
            'slug' => 'sambutan-sebelumnya-untuk-urutan',
            'status' => 'draft',
            'sort_order' => 7,
            'metadata' => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', $type), [
                'title' => 'Sambutan Otomatis Uji',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.content.index', $type));

        $this->actingAs($admin, 'admin')
            ->post(route('admin.content.store', $type), [
                'title' => 'Sambutan Otomatis Uji',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.content.index', $type));

        $entries = PrincipalWelcome::query()
            ->where('title', 'Sambutan Otomatis Uji')
            ->orderBy('id')
            ->get();

        $this->assertSame(
            ['sambutan-otomatis-uji', 'sambutan-otomatis-uji-2'],
            $entries->pluck('slug')->all(),
        );
        $this->assertSame([8, 9], $entries->pluck('sort_order')->all());
        $this->assertNull($entries[0]->published_at);
        $this->assertNull($entries[1]->published_at);
    }

    public function test_principal_welcome_migration_preserves_existing_settings(): void
    {
        ContentEntry::query()->where('type', 'principal_welcome')->delete();

        $legacySettings = [
            'home.principal.visible' => true,
            'home.principal.name' => 'Kepala Sekolah Lama',
            'home.principal.position' => 'Kepala SMK Islamiyah Ciputat',
            'home.principal.greeting' => 'Salam lama.',
            'home.principal.message' => 'Isi sambutan lama.',
            'home.principal.description' => 'Deskripsi lama.',
            'home.principal.image' => 'cms/settings/kepala.jpg',
        ];

        foreach ($legacySettings as $key => $value) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => 'home', 'value' => json_encode($value, JSON_THROW_ON_ERROR)],
            );
        }

        $migration = require database_path('migrations/2026_11_12_031213_migrate_principal_welcome_settings_to_content_entries.php');
        $migration->up();

        $entry = ContentEntry::query()->where('type', 'principal_welcome')->firstOrFail();

        $this->assertSame('Kepala Sekolah Lama', $entry->metadata['name']);
        $this->assertSame('Deskripsi lama.', $entry->metadata['description']);
        $this->assertSame('Isi sambutan lama.', $entry->body);
        $this->assertSame('cms/settings/kepala.jpg', $entry->image_path);
        $this->assertDatabaseMissing('site_settings', ['key' => 'home.principal.message']);
    }

    public function test_settings_update_identity_and_footer_for_public_pages(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'identity',
                'settings' => [
                    'identity' => [
                        'school_name' => 'SMK Islamiyah Modern',
                        'location' => 'Ciputat, Kota Tangerang Selatan',
                        'address' => 'Jl. Pendidikan No. 1',
                        'whatsapp' => '628123456789',
                        'email' => 'info@example.test',
                        'instagram' => 'https://instagram.com/smkislamiyah',
                        'copyright' => 'Pusat Informasi Sekolah',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'identity']));

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'footer',
                'settings' => [
                    'footer' => [
                        'description' => 'Informasi resmi sekolah.',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'footer']));

        $this->assertSame(
            'SMK Islamiyah Modern',
            json_decode(SiteSetting::query()->findOrFail('identity.school_name')->value, true, flags: JSON_THROW_ON_ERROR),
        );
        $this->assertSame(
            'Pusat Informasi Sekolah',
            json_decode(SiteSetting::query()->findOrFail('identity.copyright')->value, true, flags: JSON_THROW_ON_ERROR),
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('SMK Islamiyah Modern')
            ->assertSee('Ciputat, Kota Tangerang Selatan')
            ->assertSee('Informasi resmi sekolah.')
            ->assertSee('Pusat Informasi Sekolah')
            ->assertSee('https://instagram.com/smkislamiyah', false);
    }

    public function test_settings_reject_unsafe_link_schemes(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'navbar',
                'settings' => [
                    'navbar' => [
                        'home' => ['url' => 'javascript:alert(1)'],
                    ],
                ],
            ])
            ->assertSessionHasErrors(['settings.navbar.home.url']);

        $this->assertDatabaseMissing('site_settings', ['key' => 'navbar.home.url']);
    }

    public function test_admin_can_change_the_spmb_information_url_and_public_button_uses_it(): void
    {
        $admin = $this->createAdmin();
        $informationUrl = 'https://pmb.example.test/pendaftaran';

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index', ['section' => 'spmb']))
            ->assertOk()
            ->assertSee('Link tombol Informasi SPMB')
            ->assertSee('value="https://informasi-spmb.site/spmb/banten/sekolah/20603254"', false);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'spmb',
                'settings' => [
                    'spmb' => [
                        'information_url' => $informationUrl,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'spmb']));

        $this->assertSame(
            $informationUrl,
            json_decode(SiteSetting::query()->findOrFail('spmb.information_url')->value, true, flags: JSON_THROW_ON_ERROR),
        );

        $this->get(route('spmb'))
            ->assertOk()
            ->assertSee('href="'.$informationUrl.'"', false);
    }

    public function test_admin_can_edit_about_profile_and_map_details(): void
    {
        $admin = $this->createAdmin();
        $coordinates = '-6.12345, 106.54321';
        $settings = [
            'profile_heading' => 'Profil Sekolah Uji',
            'profile_paragraph_1' => 'Paragraf profil pertama yang baru.',
            'profile_paragraph_2' => 'Paragraf profil kedua yang baru.',
            'profile_image_alt' => 'Gedung sekolah tampak dari depan',
            'location_address' => 'Jl. Contoh No. 10, Ciputat',
            'location_description' => 'Peta lokasi sekolah yang telah diperbarui.',
            'map_coordinates' => $coordinates,
            'visible' => true,
        ];

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index', ['section' => 'about']))
            ->assertOk()
            ->assertSee('Foto sekolah')
            ->assertSee('Koordinat Google Maps (lintang, bujur)')
            ->assertSee('placeholder="Contoh: -6.2, 106.7"', false)
            ->assertSee('Paragraf pertama Profil Sekolah')
            ->assertSee('SMK Islamiyah Ciputat membentuk siswa dengan pengetahuan');

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('/images/10.jpeg', false)
            ->assertSee('SMK Islamiyah Ciputat membentuk siswa dengan pengetahuan');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'about',
                'settings' => ['about' => $settings],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'about']));

        foreach ($settings as $key => $value) {
            $this->assertSame(
                $value,
                json_decode(SiteSetting::query()->findOrFail('about.'.$key)->value, true, flags: JSON_THROW_ON_ERROR),
            );
        }

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Profil Sekolah Uji')
            ->assertSee('Paragraf profil pertama yang baru.')
            ->assertSee('Paragraf profil kedua yang baru.')
            ->assertSee('alt="Gedung sekolah tampak dari depan"', false)
            ->assertSee('Jl. Contoh No. 10, Ciputat')
            ->assertSee('Peta lokasi sekolah yang telah diperbarui.')
            ->assertSee('https://www.google.com/maps?q=-6.12345%2C+106.54321&output=embed', false)
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=-6.12345%2C+106.54321', false);
    }

    #[DataProvider('invalidAboutMapCoordinates')]
    public function test_admin_cannot_save_invalid_about_map_coordinates(string $coordinates): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'about',
                'settings' => [
                    'about' => [
                        'map_coordinates' => $coordinates,
                    ],
                ],
            ])
            ->assertSessionHasErrors(['settings.about.map_coordinates']);

        $this->assertDatabaseMissing('site_settings', ['key' => 'about.map_coordinates']);
    }

    public static function invalidAboutMapCoordinates(): array
    {
        return [
            'malformed coordinates' => ['not coordinates'],
            'latitude outside range' => ['91, 0'],
            'longitude outside range' => ['0, 181'],
        ];
    }

    public function test_admin_cannot_set_an_unsafe_spmb_information_url(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'spmb',
                'settings' => [
                    'spmb' => [
                        'information_url' => 'javascript:alert(1)',
                    ],
                ],
            ])
            ->assertSessionHasErrors(['settings.spmb.information_url']);

        $this->assertDatabaseMissing('site_settings', ['key' => 'spmb.information_url']);
    }

    public function test_settings_accept_internal_links(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'navbar',
                'settings' => [
                    'navbar' => [
                        'home' => ['url' => '/custom-page'],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'navbar']));

        $this->assertSame(
            '/custom-page',
            json_decode(SiteSetting::query()->findOrFail('navbar.home.url')->value, true, flags: JSON_THROW_ON_ERROR),
        );
    }

    #[DataProvider('supportedHomepageVideoUrls')]
    public function test_admin_can_save_supported_homepage_video_urls(string $url): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'home',
                'settings' => [
                    'home' => [
                        'news' => [
                            'video_1' => [
                                'source' => 'url',
                                'url' => $url,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'home']));

        $this->assertSame(
            $url,
            json_decode(SiteSetting::query()->findOrFail('home.news.video_1')->value, true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public static function supportedHomepageVideoUrls(): array
    {
        return [
            'youtube watch URL' => ['https://www.youtube.com/watch?v=AbCdeFGhi12'],
            'direct video file URL' => ['https://cdn.example.test/videos/kegiatan-sekolah.mp4'],
        ];
    }

    public function test_admin_rejects_urls_without_a_supported_video_player(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'home',
                'settings' => [
                    'home' => [
                        'news' => [
                            'video_1' => [
                                'source' => 'url',
                                'url' => 'https://vimeo.com/123456789',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors('settings.home.news.video_1.url');

        $this->assertDatabaseMissing('site_settings', ['key' => 'home.news.video_1']);
    }

    public function test_admin_can_upload_a_video_from_device_for_homepage(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'home',
                'settings' => [
                    'home' => [
                        'news' => [
                            'video_1' => [
                                'source' => 'upload',
                            ],
                        ],
                    ],
                ],
                'video_uploads' => [
                    'home' => [
                        'news' => [
                            'video_1' => UploadedFile::fake()->create('kegiatan-sekolah.mp4', 100, 'video/mp4'),
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'home']));

        $asset = MediaAsset::query()->where('filename', 'kegiatan-sekolah.mp4')->firstOrFail();
        $this->assertSame(
            $asset->publicPath(),
            json_decode(SiteSetting::query()->findOrFail('home.news.video_1')->value, true, flags: JSON_THROW_ON_ERROR),
        );
        Storage::disk('public')->assertExists($asset->publicPath());
        $this->assertContains(
            ['type' => 'Pengaturan Website', 'title' => 'home.news.video_1'],
            app(MediaUsage::class)->forAssets(collect([$asset]))[$asset->id],
        );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index', ['section' => 'home']))
            ->assertOk()
            ->assertSee('Pilih video dari perangkat')
            ->assertSee('Pertahankan video tersimpan')
            ->assertDontSee('Media Manager');
    }

    public function test_replacing_a_homepage_video_url_removes_the_unused_local_video_asset(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $video = $this->createMediaAsset('video', 'cms/media/videos/homepage-old.mp4', 'video/mp4');
        SiteSetting::query()->create([
            'key' => 'home.news.video_1',
            'group' => 'home',
            'value' => json_encode($video->publicPath(), JSON_THROW_ON_ERROR),
        ]);
        app(SiteConfiguration::class)->forgetCache();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'home',
                'settings' => [
                    'home' => [
                        'news' => [
                            'video_1' => [
                                'source' => 'url',
                                'url' => 'https://cdn.example.test/videos/homepage-new.mp4',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => 'home']));

        $this->assertDatabaseMissing('media_assets', ['id' => $video->id]);
        Storage::disk('public')->assertMissing($video->publicPath());
        $this->assertSame(
            'https://cdn.example.test/videos/homepage-new.mp4',
            json_decode(SiteSetting::query()->findOrFail('home.news.video_1')->value, true, flags: JSON_THROW_ON_ERROR),
        );
        NewsArticle::query()->create([
            'title' => 'Berita Dengan Video Beranda',
            'slug' => 'berita-dengan-video-beranda',
            'summary' => 'Aktivitas sekolah.',
            'image_path' => 'https://example.test/news.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://cdn.example.test/videos/homepage-new.mp4', false);
    }

    public function test_uploaded_setting_video_is_cleaned_up_when_setting_save_is_vetoed(): void
    {
        Storage::fake('public');
        SiteSetting::saving(fn (SiteSetting $setting): bool => false);
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'home',
                'settings' => [
                    'home' => [
                        'news' => [
                            'video_1' => ['source' => 'upload'],
                        ],
                    ],
                ],
                'video_uploads' => [
                    'home' => [
                        'news' => [
                            'video_1' => UploadedFile::fake()->create('unsaved-setting.mp4', 100, 'video/mp4'),
                        ],
                    ],
                ],
            ])
            ->assertStatus(500);

        $this->assertDatabaseCount('site_settings', 0);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('cms/media/videos'));
    }

    public function test_admin_cannot_use_an_image_as_a_homepage_video(): void
    {
        $admin = $this->createAdmin();
        $asset = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'foto-sekolah.jpg',
            'original_path' => 'cms/media/images/foto-sekolah.jpg',
            'media_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'quality' => 'original',
            'category' => 'news',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => 'home',
                'settings' => [
                    'home' => [
                        'news' => [
                            'video_1' => [
                                'source' => 'media',
                                'asset_id' => $asset->id,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
        $this->assertSame(
            ['settings.home.news.video_1.asset_id'],
            array_keys(session('errors')->getBag('default')->messages()),
        );

        $this->assertDatabaseMissing('site_settings', ['key' => 'home.news.video_1']);
    }

    #[DataProvider('settingsSections')]
    public function test_admin_can_save_settings_in_each_section(
        string $section,
        array $settings,
        string $key,
        string $expectedValue,
    ): void {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'section' => $section,
                'settings' => [$section => $settings],
            ])
            ->assertRedirect(route('admin.settings.index', ['section' => $section]));

        $this->assertSame(
            $expectedValue,
            json_decode(SiteSetting::query()->findOrFail($key)->value, true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public static function settingsSections(): array
    {
        return [
            'identity' => ['identity', ['school_name' => 'SMK Uji'], 'identity.school_name', 'SMK Uji'],
            'navbar' => ['navbar', ['home' => ['label' => 'Awal']], 'navbar.home.label', 'Awal'],
            'homepage' => ['home', ['hero' => ['slogan' => 'Belajar bersama']], 'home.hero.slogan', 'Belajar bersama'],
            'about' => ['about', ['title' => 'Profil Sekolah'], 'about.title', 'Profil Sekolah'],
            'teachers' => ['teachers', ['title' => 'Pendidik'], 'teachers.title', 'Pendidik'],
            'bkk' => ['bkk', ['title' => 'Peluang Kerja'], 'bkk.title', 'Peluang Kerja'],
            'spmb' => ['spmb', ['title' => 'Penerimaan Siswa'], 'spmb.title', 'Penerimaan Siswa'],
            'footer' => ['footer', ['description' => 'Informasi sekolah'], 'footer.description', 'Informasi sekolah'],
            'seo' => ['seo', ['title' => 'SMK Islamiyah Ciputat'], 'seo.title', 'SMK Islamiyah Ciputat'],
        ];
    }

    public function test_homepage_renders_admin_managed_news_section_copy(): void
    {
        SiteSetting::query()->create([
            'key' => 'home.news.heading',
            'group' => 'home',
            'value' => json_encode('Judul Berita Uji', JSON_THROW_ON_ERROR),
        ]);
        SiteSetting::query()->create([
            'key' => 'home.news.description',
            'group' => 'home',
            'value' => json_encode('Deskripsi berita dari pengaturan admin.', JSON_THROW_ON_ERROR),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Judul Berita Uji')
            ->assertSee('Deskripsi berita dari pengaturan admin.');
    }

    public function test_homepage_renders_admin_managed_teacher_section_copy(): void
    {
        SiteSetting::query()->create([
            'key' => 'home.teachers.heading',
            'group' => 'home',
            'value' => json_encode('Pendidik Unggul', JSON_THROW_ON_ERROR),
        ]);
        SiteSetting::query()->create([
            'key' => 'home.teachers.description',
            'group' => 'home',
            'value' => json_encode('Mendampingi masa depan siswa.', JSON_THROW_ON_ERROR),
        ]);
        SiteSetting::query()->create([
            'key' => 'home.teachers.image',
            'group' => 'home',
            'value' => json_encode('/images/test-teacher-section.jpg', JSON_THROW_ON_ERROR),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pendidik Unggul')
            ->assertSee('Mendampingi masa depan siswa.')
            ->assertSee('/images/test-teacher-section.jpg', false);
    }

    public function test_teacher_page_renders_admin_managed_title_and_description(): void
    {
        SiteSetting::query()->create([
            'key' => 'teachers.title',
            'group' => 'teachers',
            'value' => json_encode('Tenaga Pendidik Kami', JSON_THROW_ON_ERROR),
        ]);
        SiteSetting::query()->create([
            'key' => 'teachers.description',
            'group' => 'teachers',
            'value' => json_encode('Kenali para pendidik sekolah.', JSON_THROW_ON_ERROR),
        ]);

        $this->get(route('teachers'))
            ->assertOk()
            ->assertSee('Tenaga Pendidik Kami')
            ->assertSee('Kenali para pendidik sekolah.');
    }

    public function test_public_news_escapes_admin_provided_html(): void
    {
        $article = NewsArticle::query()->create([
            'title' => '<script>alert(1)</script>',
            'slug' => 'safe-rendering-test',
            'summary' => '<img src=x onerror=alert(1)>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertSee('&lt;img', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_superadmin_can_manage_users(): void
    {
        $superadmin = $this->createAdmin('superadmin');

        $this->actingAs($superadmin, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Staf Baru',
                'username' => 'stafbaru',
                'role' => 'admin',
                'password' => 'password1234',
                'password_confirmation' => 'password1234',
            ])
            ->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseHas('admins', [
            'username' => 'stafbaru',
            'role' => 'admin',
        ]);

        $created = Admin::query()->where('username', 'stafbaru')->firstOrFail();

        $this->actingAs($superadmin, 'admin')
            ->put(route('admin.admins.update', $created), [
                'name' => 'Staf Baru Edit',
                'username' => 'stafbaruedit',
                'role' => 'admin',
                'password' => 'newpassword1234',
                'password_confirmation' => 'newpassword1234',
            ])
            ->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseHas('admins', [
            'username' => 'stafbaruedit',
        ]);

        $this->actingAs($superadmin, 'admin')
            ->delete(route('admin.admins.destroy', $created))
            ->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseMissing('admins', [
            'id' => $created->id,
        ]);
    }

    public function test_superadmin_can_create_editor_but_cannot_create_another_superadmin(): void
    {
        $superadmin = $this->createAdmin('superadmin');

        $this->actingAs($superadmin, 'admin')
            ->get(route('admin.admins.create'))
            ->assertOk()
            ->assertSee('Editor — konten dan unggah foto konten')
            ->assertDontSee('value="superadmin"', false);

        $this->actingAs($superadmin, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Editor Konten',
                'username' => 'editorkonten',
                'role' => 'editor',
                'password' => 'password1234',
                'password_confirmation' => 'password1234',
            ])
            ->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseHas('admins', [
            'username' => 'editorkonten',
            'role' => 'editor',
        ]);

        $this->actingAs($superadmin, 'admin')
            ->from(route('admin.admins.create'))
            ->post(route('admin.admins.store'), [
                'name' => 'Superadmin Kedua',
                'username' => 'superadminkedua',
                'role' => 'superadmin',
                'password' => 'password1234',
                'password_confirmation' => 'password1234',
            ])
            ->assertRedirect(route('admin.admins.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('admins', [
            'username' => 'superadminkedua',
        ]);
        $this->assertSame(1, Admin::query()->where('role', 'superadmin')->count());
    }

    public function test_admin_create_command_allows_only_the_first_superadmin(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nama admin', 'Super Administrator')
            ->expectsQuestion('Username admin', 'superadminpertama')
            ->expectsQuestion('Role / Peran', 'superadmin')
            ->expectsQuestion('Email admin (opsional, tekan Enter untuk lewati)', '')
            ->expectsQuestion('Kata sandi (minimal 8 karakter)', 'Secure-password-1234')
            ->expectsQuestion('Ulangi kata sandi', 'Secure-password-1234')
            ->assertExitCode(0);

        $this->assertDatabaseHas('admins', [
            'username' => 'superadminpertama',
            'role' => 'superadmin',
        ]);

        $this->artisan('admin:create')
            ->expectsQuestion('Nama admin', 'Editor Konten')
            ->expectsQuestion('Username admin', 'editorpertama')
            ->expectsQuestion('Role / Peran', 'editor')
            ->expectsQuestion('Email admin (opsional, tekan Enter untuk lewati)', '')
            ->expectsQuestion('Kata sandi (minimal 8 karakter)', 'Secure-password-1234')
            ->expectsQuestion('Ulangi kata sandi', 'Secure-password-1234')
            ->assertExitCode(0);

        $this->assertDatabaseHas('admins', [
            'username' => 'editorpertama',
            'role' => 'editor',
        ]);
        $this->assertSame(1, Admin::query()->where('role', 'superadmin')->count());
    }

    public function test_cms_content_seeder_does_not_create_a_predictable_admin_account(): void
    {
        $this->seed(CmsContentSeeder::class);

        $this->assertDatabaseMissing('admins', [
            'username' => 'superadmin',
        ]);
    }

    public function test_editor_can_manage_content_but_cannot_access_admin_features(): void
    {
        $editor = $this->createAdmin('editor');

        $this->actingAs($editor, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Peran Anda: <strong>Editor</strong>', false)
            ->assertSee('Editor dapat mengunggah media untuk konten')
            ->assertDontSee('Media Manager')
            ->assertDontSee('href="'.route('admin.settings.index').'"', false)
            ->assertDontSee('Manajemen Admin');

        $this->actingAs($editor, 'admin')
            ->get(route('admin.content.index', 'programs'))
            ->assertOk();

        $this->actingAs($editor, 'admin')
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->actingAs($editor, 'admin')
            ->get('/adminsmkiciputat/media')
            ->assertNotFound();

        $this->actingAs($editor, 'admin')
            ->get(route('admin.admins.index'))
            ->assertForbidden();
    }

    public function test_regular_admin_cannot_access_user_management(): void
    {
        $admin = $this->createAdmin('admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.admins.index'))
            ->assertForbidden();
    }

    public function test_admin_can_manage_teacher_using_default_database(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.teachers.store'), [
                'title' => 'Guru Uji',
                'slug' => 'guru-uji',
                'summary' => 'Profil singkat guru uji.',
                'status' => 'published',
                'metadata' => [
                    'position' => 'Guru Matematika',
                    'education' => 'S1 Pendidikan',
                ],
            ])
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('teachers', [
            'name' => 'Guru Uji',
            'slug' => 'guru-uji',
            'status' => 'published',
        ]);

        $this->assertDatabaseMissing('content_entries', [
            'type' => 'teacher',
            'slug' => 'guru-uji',
        ]);

        $teacher = Teacher::query()->where('slug', 'guru-uji')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.teachers.update', $teacher), [
                'title' => 'Guru Uji Edit',
                'summary' => 'Profil diperbarui.',
                'status' => 'published',
                'metadata' => [
                    'position' => 'Guru IPA',
                    'education' => 'S2 Pendidikan',
                ],
            ])
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Guru Uji Edit',
            'slug' => 'guru-uji',
        ]);

        $this->get(route('teachers'))
            ->assertOk()
            ->assertSee('Guru Uji Edit');

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('teachers', [
            'id' => $teacher->id,
        ]);
        $this->assertNull(Teacher::query()->find($teacher->id));

        $this->assertDatabaseHas('content_activities', [
            'content_type' => 'teacher',
            'content_id' => $teacher->id,
            'action' => 'deleted',
            'title' => 'Guru Uji Edit',
        ]);

        $this->get(route('teachers'))
            ->assertOk()
            ->assertDontSee('Guru Uji Edit');
    }

    public function test_public_teacher_directory_filters_by_teacher_category(): void
    {
        $guru = $this->createTeacher([
            'name' => 'Siti Guru',
            'slug' => 'siti-guru',
            'category' => 'Tenaga Pendidik',
        ]);
        $staff = $this->createTeacher([
            'name' => 'Budi Tenaga Kependidikan',
            'slug' => 'budi-tenaga-kependidikan',
            'category' => 'Tenaga Kependidikan',
        ]);

        $this->get(route('teachers', ['category' => 'guru']))
            ->assertOk()
            ->assertSee($guru->name)
            ->assertDontSee($staff->name);
    }

    public function test_public_teacher_directory_filters_by_support_staff_category(): void
    {
        $guru = $this->createTeacher([
            'name' => 'Siti Guru',
            'slug' => 'siti-guru',
            'category' => 'Tenaga Pendidik',
        ]);
        $staff = $this->createTeacher([
            'name' => 'Budi Tenaga Kependidikan',
            'slug' => 'budi-tenaga-kependidikan',
            'category' => 'Tenaga Kependidikan',
        ]);

        $this->get(route('teachers', ['category' => 'kependidikan']))
            ->assertOk()
            ->assertSee($staff->name)
            ->assertDontSee($guru->name);
    }

    public function test_public_teacher_directory_search_and_pagination_keep_the_selected_filters(): void
    {
        foreach (range(1, 9) as $number) {
            $this->createTeacher([
                'name' => sprintf('Guru Pendidik %02d', $number),
                'slug' => sprintf('guru-pendidik-%02d', $number),
                'category' => 'Tenaga Pendidik',
            ]);
        }

        $this->get(route('teachers', [
            'category' => 'guru',
            'search' => 'Pendidik',
        ]))
            ->assertOk()
            ->assertSee('Guru Pendidik 01')
            ->assertDontSee('Guru Pendidik 09')
            ->assertSee('category=guru', false)
            ->assertSee('search=Pendidik', false)
            ->assertSee('page=2', false);

        $this->get(route('teachers', [
            'category' => 'guru',
            'search' => 'Pendidik',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('Guru Pendidik 09');
    }

    public function test_teacher_edit_does_not_create_duplicate_record(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['name' => 'Guru A', 'slug' => 'guru-a', 'position' => 'Guru']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.teachers.update', $teacher), $this->teacherPayload([
                'title' => 'Guru A Edit',
                'slug' => 'guru-a-edit',
                'metadata' => ['position' => 'Guru IPA', 'education' => 'S1'],
            ]))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertSame(1, Teacher::query()->count());
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'name' => 'Guru A Edit']);
    }

    public function test_teacher_photo_replaced_and_old_file_cleaned_when_unused(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['image_path' => UploadedFile::fake()->image('old.jpg')->storePublicly('cms/teacher', 'public')]);
        $oldPath = $teacher->image_path;
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.teachers.update', $teacher), $this->teacherPayload([
                'title' => $teacher->name,
                'slug' => $teacher->slug,
                'metadata' => ['position' => $teacher->position, 'education' => $teacher->education],
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]))
            ->assertRedirect(route('admin.teachers.index'));

        $teacher->refresh();
        $this->assertNotSame($oldPath, $teacher->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($teacher->image_path);
    }

    public function test_teacher_photo_focus_is_saved_and_rendered_on_public_id_cards(): void
    {
        $admin = $this->createAdmin();
        $asset = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'guru.webp',
            'original_path' => 'cms/media/originals/guru-focus.png',
            'optimized_path' => 'cms/media/optimized/guru-focus.webp',
            'media_type' => 'image',
            'mime_type' => 'image/png',
            'file_size' => 1000,
            'optimized_size' => 800,
            'width' => 600,
            'height' => 900,
            'quality' => 'optimized',
            'category' => 'teacher',
            'processing' => ['output_mime_type' => 'image/webp'],
        ]);
        $teacher = $this->createTeacher(['image_path' => $asset->publicPath()]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.teachers.update', $teacher), $this->teacherPayload([
                'title' => $teacher->name,
                'slug' => $teacher->slug,
                'metadata' => ['position' => $teacher->position, 'education' => null],
                'focal_point_x' => 63,
                'focal_point_y' => 34,
            ]))
            ->assertRedirect(route('admin.teachers.index'));

        $asset->refresh();
        $this->assertSame('image/webp', $asset->processing['output_mime_type']);
        $this->assertSame(63.0, (float) data_get($asset->processing, 'focal_point.x'));
        $this->assertSame(34.0, (float) data_get($asset->processing, 'focal_point.y'));

        $this->get(route('teachers'))
            ->assertOk()
            ->assertSee('--staff-focal-x: 63%; --staff-focal-y: 34%', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--staff-focal-x: 63%; --staff-focal-y: 34%', false);
    }

    public function test_teacher_photo_focus_rejects_values_outside_the_image_bounds(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.teachers.update', $teacher), $this->teacherPayload([
                'title' => $teacher->name,
                'slug' => $teacher->slug,
                'metadata' => ['position' => $teacher->position, 'education' => null],
                'focal_point_x' => 101,
                'focal_point_y' => 24,
            ]))
            ->assertSessionHasErrors(['focal_point_x']);
    }

    public function test_teacher_edit_without_photo_keeps_existing_photo(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $path = UploadedFile::fake()->image('keep.jpg')->storePublicly('cms/teacher', 'public');
        $teacher = $this->createTeacher(['image_path' => $path]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.teachers.update', $teacher), $this->teacherPayload([
                'title' => 'Nama Baru',
                'slug' => $teacher->slug,
                'metadata' => ['position' => $teacher->position, 'education' => $teacher->education],
            ]))
            ->assertRedirect(route('admin.teachers.index'));

        $teacher->refresh();
        $this->assertSame($path, $teacher->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_teacher_delete_removes_record_and_unused_photo(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $path = UploadedFile::fake()->image('gone.jpg')->storePublicly('cms/teacher', 'public');
        $teacher = $this->createTeacher(['image_path' => $path]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_teacher_delete_keeps_shared_photo_still_in_use(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $path = UploadedFile::fake()->image('shared.jpg')->storePublicly('cms/teacher', 'public');
        $teacher = $this->createTeacher(['name' => 'Guru 1', 'slug' => 'guru-1', 'image_path' => $path]);
        $this->createTeacher(['name' => 'Guru 2', 'slug' => 'guru-2', 'position' => 'Guru B', 'image_path' => $path]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_teacher_duplicate_name_and_position_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $this->createTeacher(['name' => 'Guru Sama', 'slug' => 'guru-sama', 'position' => 'Guru Matematika']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.teachers.store'), $this->teacherPayload([
                'title' => 'Guru Sama',
                'metadata' => ['position' => 'Guru Matematika', 'education' => null],
            ]))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Teacher::query()->count());
    }

    public function test_teacher_bulk_delete_and_empty_category(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $t1 = $this->createTeacher(['name' => 'Guru 1', 'slug' => 'guru-1', 'image_path' => UploadedFile::fake()->image('a.jpg')->storePublicly('cms/teacher', 'public')]);
        $t2 = $this->createTeacher(['name' => 'Guru 2', 'slug' => 'guru-2', 'position' => 'Guru B']);
        $t3 = $this->createTeacher(['name' => 'Guru 3', 'slug' => 'guru-3', 'position' => 'Guru C']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.teachers.bulk-destroy'), ['ids' => [$t1->id, $t2->id]])
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('teachers', ['id' => $t1->id]);
        $this->assertDatabaseMissing('teachers', ['id' => $t2->id]);
        $this->assertDatabaseHas('teachers', ['id' => $t3->id]);
        Storage::disk('public')->assertMissing($t1->image_path);
        $this->assertDatabaseHas('content_activities', ['content_type' => 'teacher', 'content_id' => $t1->id, 'action' => 'deleted']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.teachers.empty'), ['confirm_name' => 'TENAGA KEPENDIDIKAN'])
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertSame(0, Teacher::query()->count());
        $this->get(route('teachers'))
            ->assertOk()
            ->assertDontSee('Guru 1')
            ->assertDontSee('Guru 2')
            ->assertDontSee('Guru 3');
    }

    public function test_admin_can_set_teacher_employment_status_and_it_is_shown_in_admin_and_public(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.teachers.store'), $this->teacherPayload([
                'title' => 'Guru Aktif',
                'slug' => 'guru-aktif',
                'employment_status' => 'active',
            ]))
            ->assertRedirect(route('admin.teachers.index'));

        $this->actingAs($admin, 'admin')
            ->post(route('admin.teachers.store'), $this->teacherPayload([
                'title' => 'Guru Tidak Aktif',
                'slug' => 'guru-tidak-aktif',
                'employment_status' => 'inactive',
                'metadata' => ['position' => 'Guru Sejarah', 'education' => 'S1'],
            ]))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('teachers', ['slug' => 'guru-aktif', 'employment_status' => 'active']);
        $this->assertDatabaseHas('teachers', ['slug' => 'guru-tidak-aktif', 'employment_status' => 'inactive']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertSee('Masih Aktif')
            ->assertSee('Sudah Tidak Aktif');

        $this->get(route('teachers'))
            ->assertOk()
            ->assertSee('Guru Aktif')
            ->assertSee('Guru Tidak Aktif')
            ->assertSee('Masih Aktif')
            ->assertSee('Sudah Tidak Aktif');
    }

    public function test_public_teacher_directory_filters_by_employment_status(): void
    {
        $activeTeacher = $this->createTeacher([
            'name' => 'Guru Masih Aktif',
            'slug' => 'guru-masih-aktif',
            'employment_status' => 'active',
        ]);
        $inactiveTeacher = $this->createTeacher([
            'name' => 'Guru Sudah Tidak Aktif',
            'slug' => 'guru-sudah-tidak-aktif',
            'employment_status' => 'inactive',
        ]);

        $this->get(route('teachers', ['employment_status' => 'active']))
            ->assertOk()
            ->assertSee($activeTeacher->name)
            ->assertDontSee($inactiveTeacher->name);

        $this->get(route('teachers', ['employment_status' => 'inactive']))
            ->assertOk()
            ->assertSee($inactiveTeacher->name)
            ->assertDontSee($activeTeacher->name);

        $this->get(route('teachers', ['employment_status' => 'all']))
            ->assertOk()
            ->assertSee($activeTeacher->name)
            ->assertSee($inactiveTeacher->name);
    }

    private function createAdmin(string $role = 'superadmin'): Admin
    {
        return Admin::query()->create([
            'name' => 'CMS Editor',
            'username' => 'editor',
            'role' => $role,
            'email' => 'editor@example.test',
            'password' => Hash::make('Secure-password-1234'),
        ]);
    }

    private function createMediaAsset(string $mediaType, string $path, string $mimeType): MediaAsset
    {
        Storage::disk('public')->put($path, 'media-data');

        return MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => basename($path),
            'original_path' => $path,
            'optimized_path' => $path,
            'media_type' => $mediaType,
            'mime_type' => $mimeType,
            'file_size' => 10,
            'optimized_size' => 10,
            'quality' => 'original',
            'category' => 'news',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTeacher(array $attributes = []): Teacher
    {
        $defaults = [
            'name' => 'Guru Default',
            'slug' => 'guru-default-'.Str::random(4),
            'position' => 'Guru Umum',
            'education' => null,
            'category' => 'Tenaga Pendidik',
            'summary' => null,
            'image_path' => null,
            'status' => 'published',
            'employment_status' => 'active',
            'sort_order' => 0,
            'published_at' => now(),
        ];

        return Teacher::query()->create(array_merge($defaults, $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function teacherPayload(array $overrides = []): array
    {
        $payload = [
            'title' => 'Guru Payload',
            'slug' => 'guru-payload',
            'summary' => 'Ringkasan',
            'status' => 'published',
            'employment_status' => 'active',
            'metadata' => [
                'position' => 'Guru Payload',
                'education' => 'S1',
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
