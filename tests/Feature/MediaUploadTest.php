<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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
