<?php

namespace App\Models;

use App\Support\VideoSource;

class NewsArticle extends ContentRecord
{
    protected $table = 'news_articles';

    public static function contentType(): string
    {
        return 'news';
    }

    /**
     * @return array<int, array{type: string, src: string, poster: ?string, mime_type: ?string, youtube_id: ?string}>
     */
    public function getMediaItemsAttribute(): array
    {
        $storedMedia = data_get($this->metadata, 'media', []);
        $items = is_array($storedMedia)
            ? array_values(array_filter($storedMedia, 'is_array'))
            : [];
        $coverPath = $this->image_path;

        if (
            filled($coverPath)
            && ! collect($items)->contains(fn (array $item): bool => in_array(
                $coverPath,
                [$item['path'] ?? null, $item['url'] ?? null],
                true,
            ))
        ) {
            array_unshift($items, [
                'type' => 'image',
                'source' => 'existing',
                'path' => $coverPath,
            ]);
        }

        $normalized = collect($items)
            ->map(fn (array $item): ?array => $this->normalizeMediaItem($item))
            ->filter()
            ->values();

        $legacyImages = data_get($this->metadata, 'images', []);
        $legacyImages = is_array($legacyImages) ? $legacyImages : [];

        foreach ($legacyImages as $image) {
            $legacyImage = is_array($image)
                ? $image
                : ['type' => 'image', 'source' => 'existing', 'path' => $image];
            $normalizedImage = $this->normalizeMediaItem($legacyImage);

            if (
                $normalizedImage !== null
                && ! $normalized->contains(fn (array $item): bool => $item['src'] === $normalizedImage['src'])
            ) {
                $normalized->push($normalizedImage);
            }
        }

        return $normalized->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{type: string, src: string, poster: ?string, mime_type: ?string, youtube_id: ?string}|null
     */
    private function normalizeMediaItem(array $item): ?array
    {
        $type = $item['type'] ?? 'image';
        $source = $item['source'] ?? 'existing';
        $src = $source === 'url' ? ($item['url'] ?? null) : ($item['path'] ?? null);

        if (! in_array($type, ['image', 'video'], true) || ! is_string($src) || blank($src)) {
            return null;
        }

        $youtubeId = $type === 'video' ? VideoSource::youtubeId($src) : null;
        $poster = is_string($item['poster_path'] ?? null) ? $item['poster_path'] : null;

        if ($poster === null && $youtubeId !== null) {
            $poster = 'https://img.youtube.com/vi/'.$youtubeId.'/hqdefault.jpg';
        }

        return [
            'type' => $type,
            'src' => $src,
            'poster' => $poster,
            'mime_type' => is_string($item['mime_type'] ?? null)
                ? $item['mime_type']
                : $this->videoMimeType($src, $type),
            'youtube_id' => $youtubeId,
        ];
    }

    private function videoMimeType(string $src, string $type): ?string
    {
        if ($type !== 'video') {
            return null;
        }

        $extension = strtolower(pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($extension) {
            'mp4', 'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'ogv', 'ogg' => 'video/ogg',
            default => null,
        };
    }
}
