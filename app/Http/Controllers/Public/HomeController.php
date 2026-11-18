<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\PrincipalWelcome;
use App\Models\Program;
use App\Models\Teacher;
use App\Support\SiteConfiguration;
use App\Support\TeacherPhotoFocus;
use App\Support\VideoSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TeacherPhotoFocus $photoFocus, SiteConfiguration $configuration): View
    {
        $programs = Program::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $teachers = Teacher::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $photoFocus->decorate($teachers);
        $principalWelcomes = PrincipalWelcome::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $latestNewsQuery = NewsArticle::query()
            ->published()
            ->where(function (Builder $query): void {
                $query->whereNull('metadata->homepage_group')
                    ->orWhereNotIn('metadata->homepage_group', [
                        'featured',
                        'archive',
                        'news_only',
                    ]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');
        $mainArticle = NewsArticle::query()
            ->published()
            ->where('metadata->homepage_group', 'featured')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if ($mainArticle === null) {
            $mainArticle = (clone $latestNewsQuery)->first();
        }

        $featuredNews = $mainArticle === null ? collect() : collect([$mainArticle]);
        $latestNews = (clone $latestNewsQuery)
            ->when($mainArticle !== null, fn (Builder $query): Builder => $query->where('id', '<>', $mainArticle->getKey()))
            ->limit(3)
            ->get();
        $carouselNews = $featuredNews->concat($latestNews)->values();
        $belowCarouselNews = collect();
        $sidebarNews = collect();
        $newsVideos = $this->newsVideos($configuration);
        $latestActivity = $latestNews->first(fn (NewsArticle $article): bool => filled($article->image_path))
            ?? $mainArticle;
        $latestActivityUrl = match (true) {
            $latestActivity instanceof NewsArticle => route('news.show', $latestActivity->slug),
            default => null,
        };

        return view('pages.home', [
            'programs' => $programs,
            'teachers' => $teachers,
            'principalWelcomes' => $principalWelcomes,
            'featuredNews' => $featuredNews,
            'carouselNews' => $carouselNews,
            'belowCarouselNews' => $belowCarouselNews,
            'sidebarNews' => $sidebarNews,
            'newsVideos' => $newsVideos,
            'latestActivity' => $latestActivity,
            'latestActivityUrl' => $latestActivityUrl,
            'showAllNewsLink' => NewsArticle::query()->published()->count() > 4,
        ]);
    }

    /**
     * @return array<int, array{href: string}>
     */
    private function newsVideos(SiteConfiguration $configuration): array
    {
        $settings = $configuration->all();

        return collect([
            $settings['home.news.video_1'] ?? null,
            $settings['home.news.video_2'] ?? null,
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
            ->map(function (string $value) use ($configuration): array {
                $youtubeId = VideoSource::youtubeId($value);

                if ($youtubeId !== null) {
                    return [
                        'href' => 'https://www.youtube.com/watch?v='.$youtubeId,
                    ];
                }

                return [
                    'href' => $configuration->imageUrl($value),
                ];
            })
            ->filter(fn (array $video): bool => filled($video['href']))
            ->values()
            ->all();
    }
}
