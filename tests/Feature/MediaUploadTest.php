<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\MediaAsset;
use App\Support\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_no_longer_exposes_media_management(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/adminsmkiciputat/media')
            ->assertNotFound();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Media Manager')
            ->assertDontSee('Kelola Media');
    }

    public function test_media_upload_endpoint_requires_admin_authentication(): void
    {
        $this->postJson(route('admin.media.uploads.store'), [
            'filename' => 'news-photo.jpg',
            'size' => 100,
            'media_type' => 'image',
            'category' => 'news',
            'quality' => 'optimized',
        ])->assertUnauthorized();
    }

    public function test_editor_can_upload_content_images_but_not_videos(): void
    {
        $editor = $this->createAdmin('content-editor', 'editor');

        $this->actingAs($editor, 'admin')
            ->postJson(route('admin.media.uploads.store'), [
                'filename' => 'content-photo.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'media_type' => 'image',
                'category' => 'news',
                'quality' => 'optimized',
            ])
            ->assertCreated();

        $this->postJson(route('admin.media.uploads.store'), [
            'filename' => 'school-video.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'media_type' => 'video',
            'category' => 'news',
            'quality' => 'original',
        ])->assertForbidden();
    }

    public function test_editor_receives_403_when_uploading_a_video_poster(): void
    {
        Storage::fake('public');
        $editor = $this->createAdmin('content-editor', 'editor');
        $video = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'school-video.mp4',
            'original_path' => 'cms/media/videos/school-video.mp4',
            'optimized_path' => 'cms/media/videos/school-video.mp4',
            'media_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => 100,
            'optimized_size' => 100,
            'quality' => 'original',
            'category' => 'news',
        ]);

        $this->actingAs($editor, 'admin')
            ->putJson(route('admin.media.poster', $video), [
                'poster' => UploadedFile::fake()->image('poster.jpg', 320, 180),
            ])
            ->assertForbidden();

        $this->assertNull($video->fresh()->poster_path);
        $this->assertSame([], Storage::disk('public')->allFiles('cms/media/posters'));
    }

    public function test_news_form_exposes_image_and_video_file_preview_targets(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.content.create', 'news'))
            ->assertOk()
            ->assertSee('data-media-image-preview', false)
            ->assertSee('data-media-video-preview', false)
            ->assertSee('data-news-media-file-details', false);
    }

    public function test_video_file_is_removed_when_media_asset_persistence_fails(): void
    {
        Storage::fake('public');
        MediaAsset::creating(function (MediaAsset $asset): void {
            throw new RuntimeException('Simulated media record failure.');
        });

        try {
            app(MediaLibrary::class)->storeVideo(
                UploadedFile::fake()->create('failed-upload.mp4', 100, 'video/mp4'),
                'news',
            );
            $this->fail('The simulated media record failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated media record failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('cms/media/videos'));
    }

    public function test_video_upload_is_rejected_when_media_asset_creation_is_vetoed(): void
    {
        Storage::fake('public');
        MediaAsset::creating(fn (MediaAsset $asset): bool => false);

        try {
            app(MediaLibrary::class)->storeVideo(
                UploadedFile::fake()->create('vetoed-upload.mp4', 100, 'video/mp4'),
                'news',
            );
            $this->fail('A media asset without a saved database record was returned.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Data media video gagal disimpan.', $exception->getMessage());
        }

        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('cms/media/videos'));
    }

    public function test_media_file_remains_when_database_delete_is_vetoed(): void
    {
        Storage::fake('public');
        $path = 'cms/media/videos/delete-vetoed.mp4';
        Storage::disk('public')->put($path, 'video-data');
        $asset = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'delete-vetoed.mp4',
            'original_path' => $path,
            'optimized_path' => $path,
            'media_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => 10,
            'optimized_size' => 10,
            'quality' => 'original',
            'category' => 'news',
        ]);
        MediaAsset::deleting(fn (MediaAsset $asset): bool => false);

        $this->assertFalse(app(MediaLibrary::class)->delete($asset));
        $this->assertModelExists($asset->fresh());
        Storage::disk('public')->assertExists($path);
    }

    public function test_media_file_is_preserved_when_another_asset_uses_the_same_path(): void
    {
        Storage::fake('public');
        $path = 'cms/media/videos/shared-asset.mp4';
        Storage::disk('public')->put($path, 'video-data');
        $firstAsset = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'shared-asset-first.mp4',
            'original_path' => 'cms/media/videos/shared-asset-first.mp4',
            'optimized_path' => $path,
            'media_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => 10,
            'optimized_size' => 10,
            'quality' => 'original',
            'category' => 'news',
        ]);
        $secondAsset = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'shared-asset-second.mp4',
            'original_path' => 'cms/media/videos/shared-asset-second.mp4',
            'optimized_path' => 'cms/media/videos/shared-asset-second-optimized.mp4',
            'poster_path' => $path,
            'media_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => 10,
            'optimized_size' => 10,
            'quality' => 'original',
            'category' => 'news',
        ]);

        $this->assertFalse(app(MediaLibrary::class)->delete($firstAsset));
        $this->assertModelExists($firstAsset->fresh());
        $this->assertModelExists($secondAsset->fresh());
        Storage::disk('public')->assertExists($path);
    }

    public function test_media_record_and_file_remain_when_storage_cannot_stage_a_delete(): void
    {
        Storage::fake('public');
        $path = 'cms/media/videos/storage-failure.mp4';
        Storage::disk('public')->put($path, 'video-data');
        Storage::disk('public')->put('cms/media/.trash', 'blocks the staging directory');
        $asset = MediaAsset::query()->create([
            'disk' => 'public',
            'filename' => 'storage-failure.mp4',
            'original_path' => $path,
            'optimized_path' => $path,
            'media_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => 10,
            'optimized_size' => 10,
            'quality' => 'original',
            'category' => 'news',
        ]);
        $deleted = false;

        try {
            $deleted = app(MediaLibrary::class)->delete($asset);
        } catch (Throwable) {
            $deleted = false;
        }

        $this->assertFalse($deleted);
        $this->assertModelExists($asset->fresh());
        Storage::disk('public')->assertExists($path);
    }

    public function test_chunked_upload_can_resume_and_complete(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->createAdmin();
        $file = UploadedFile::fake()->image('chunked.jpg', 160, 120);
        $fileSize = $file->getSize();

        $start = $this->actingAs($admin, 'admin')->postJson(route('admin.media.uploads.store'), [
            'filename' => $file->getClientOriginalName(),
            'mime_type' => 'image/jpeg',
            'size' => $fileSize,
            'media_type' => 'image',
            'category' => 'news',
            'quality' => 'optimized',
        ])->assertCreated();
        $uploadId = $start->json('id');
        $chunk = new UploadedFile(
            $file->getRealPath(),
            'chunk.part',
            'application/octet-stream',
            UPLOAD_ERR_OK,
            true,
        );

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.media.uploads.chunk', [
                'uploadId' => $uploadId,
                'fileKey' => 'file',
                'chunkIndex' => 0,
            ]), ['chunk' => $chunk])
            ->assertOk()
            ->assertJsonPath('stored', true);

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.media.uploads.show', $uploadId))
            ->assertOk()
            ->assertJsonPath('slots.file.received.0', 0);

        $complete = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.media.uploads.complete', $uploadId))
            ->assertCreated();

        $asset = MediaAsset::query()->findOrFail($complete->json('id'));
        $this->assertSame('image', $asset->media_type);
        Storage::disk('public')->assertExists($asset->publicPath());
        $this->assertSame(
            ['media-uploads/'.$admin->id.'/'.$uploadId.'/manifest.json'],
            Storage::disk('local')->allFiles('media-uploads'),
        );

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.media.uploads.complete', $uploadId))
            ->assertOk()
            ->assertJsonPath('id', $asset->id);

        $this->assertDatabaseCount('media_assets', 1);
        $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.media.uploads.destroy', $uploadId))
            ->assertConflict();
        $this->assertModelExists($asset->fresh());
    }

    public function test_upload_start_returns_422_when_file_size_exceeds_configured_limit(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $maxBytes = config('media.max_upload_mb') * 1024 * 1024;

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.media.uploads.store'), [
                'filename' => 'too-large.mp4',
                'mime_type' => 'video/mp4',
                'size' => $maxBytes + 1,
                'media_type' => 'video',
                'category' => 'news',
                'quality' => 'original',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('size');

        $this->assertSame([], Storage::disk('local')->allFiles('media-uploads'));
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_chunked_upload_returns_422_when_file_content_does_not_match_its_declared_media_type(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->createAdmin();
        $file = UploadedFile::fake()->createWithContent('invalid.jpg', 'not an image');
        $chunk = new UploadedFile(
            $file->getRealPath(),
            'invalid.part',
            'application/octet-stream',
            UPLOAD_ERR_OK,
            true,
        );
        $uploadId = $this->actingAs($admin, 'admin')->postJson(route('admin.media.uploads.store'), [
            'filename' => $file->getClientOriginalName(),
            'mime_type' => 'image/jpeg',
            'size' => $chunk->getSize(),
            'media_type' => 'image',
            'category' => 'news',
            'quality' => 'optimized',
        ])->assertCreated()->json('id');

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.media.uploads.chunk', [
                'uploadId' => $uploadId,
                'fileKey' => 'file',
                'chunkIndex' => 0,
            ]), ['chunk' => $chunk])
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.media.uploads.complete', $uploadId))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('cms/media'));
        $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.media.uploads.destroy', $uploadId))
            ->assertNoContent();
        $this->assertSame([], Storage::disk('local')->allFiles('media-uploads'));
    }

    public function test_admin_can_cancel_an_incomplete_upload_session(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $uploadId = $this->actingAs($admin, 'admin')->postJson(route('admin.media.uploads.store'), [
            'filename' => 'pending.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'media_type' => 'image',
            'category' => 'news',
            'quality' => 'optimized',
        ])->assertCreated()->json('id');

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.media.uploads.destroy', $uploadId))
            ->assertNoContent();

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.media.uploads.show', $uploadId))
            ->assertNotFound();
        $this->assertSame([], Storage::disk('local')->allFiles('media-uploads'));
    }

    public function test_upload_session_is_not_accessible_to_another_admin(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-editor');
        $uploadId = $this->actingAs($admin, 'admin')->postJson(route('admin.media.uploads.store'), [
            'filename' => 'private.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'media_type' => 'image',
            'category' => 'news',
            'quality' => 'optimized',
        ])->assertCreated()->json('id');

        $this->actingAs($otherAdmin, 'admin')
            ->getJson(route('admin.media.uploads.show', $uploadId))
            ->assertNotFound();
    }

    private function createAdmin(string $username = 'editor', string $role = 'superadmin'): Admin
    {
        return Admin::query()->create([
            'name' => 'CMS Editor',
            'username' => $username,
            'role' => $role,
            'email' => $username.'@example.test',
            'password' => Hash::make('Secure-password-1234'),
        ]);
    }
}
