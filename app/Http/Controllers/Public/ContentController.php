<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\JobVacancy;
use App\Models\NewsArticle;
use App\Models\Teacher;
use App\Support\SiteConfiguration;
use App\Support\TeacherPhotoFocus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function about(SiteConfiguration $configuration): View
    {
        $settings = $configuration->all();
        abort_unless($settings['about.visible'], 404);

        $galleryPhotos = GalleryPhoto::query()
            ->published()
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(4)
            ->get(['id', 'title', 'summary', 'image_path']);

        return view('pages.about', [
            'galleryPhotos' => $galleryPhotos,
        ]);
    }

    public function teachers(Request $request, TeacherPhotoFocus $photoFocus): View
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'in:all,guru,kependidikan'],
            'search' => ['nullable', 'string', 'max:100'],
        ], [
            'category.in' => 'Filter kategori tidak valid.',
            'search.max' => 'Pencarian maksimal 100 karakter.',
        ]);
        $category = $filters['category'] ?? 'all';
        $search = trim($filters['search'] ?? '');

        $query = Teacher::query()
            ->published()
            ->when($category === 'guru', function (Builder $query): void {
                $query->whereIn('category', ['Guru', 'Tenaga Pendidik']);
            })
            ->when($category === 'kependidikan', function (Builder $query): void {
                $query->where('category', 'Tenaga Kependidikan');
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('sort_order')
            ->orderBy('name');

        $teachers = $query->paginate(8)->withQueryString();
        $photoFocus->decorate($teachers->getCollection());

        return view('pages.teachers', [
            'teachers' => $teachers,
            'activeCategory' => $category,
            'searchTerm' => $search,
        ]);
    }

    public function news(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($filters['search'] ?? '');
        $publishedNews = NewsArticle::query()->published();
        $categoryCounts = $this->publishedCategoryCounts($publishedNews);
        $categories = $categoryCounts->keys();
        $category = $request->query('category');

        abort_if(
            $category !== null && (! is_string($category) || ! $categories->contains($category)),
            404,
        );

        $newsQuery = (clone $publishedNews)
            ->when($category !== null, fn (Builder $query): Builder => $query->where('category', $category))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('summary', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%');
                });
            });
        $featuredArticle = (clone $newsQuery)
            ->where('metadata->homepage_group', 'featured')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if ($featuredArticle === null) {
            $featuredArticle = (clone $newsQuery)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();
        }

        $popularArticles = (clone $newsQuery)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'category', 'image_path', 'published_at', 'metadata']);
        $articles = (clone $newsQuery)
            ->when(
                $featuredArticle !== null,
                fn (Builder $query): Builder => $query->where('id', '<>', $featuredArticle->id),
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('pages.news.index', [
            'articles' => $articles,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'publishedArticleCount' => (clone $publishedNews)->count(),
            'selectedCategory' => $category,
            'searchTerm' => $search,
            'featuredArticle' => $featuredArticle,
            'popularArticles' => $popularArticles,
        ]);
    }

    public function article(string $slug): View
    {
        $article = NewsArticle::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $publishedNews = NewsArticle::query()->published();
        $relatedArticles = (clone $publishedNews)
            ->where('id', '<>', $article->getKey())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'slug', 'title', 'category', 'image_path', 'published_at', 'metadata']);

        return view('pages.news.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'categoryCounts' => $this->publishedCategoryCounts($publishedNews),
            'publishedArticleCount' => (clone $publishedNews)->count(),
        ]);
    }

    private function publishedCategoryCounts(Builder $publishedNews): Collection
    {
        return (clone $publishedNews)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->select('category')
            ->selectRaw('COUNT(*) AS articles_count')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('articles_count', 'category');
    }

    public function gallery(): View
    {
        return view('pages.gallery', [
            'photos' => GalleryPhoto::query()
                ->published()
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->paginate(12),
        ]);
    }

    public function jobs(Request $request): View
    {
        $activeJobs = JobVacancy::query()
            ->published()
            ->where(function (Builder $query): void {
                $query->whereNull('metadata->closing_date')
                    ->orWhere('metadata->closing_date', '')
                    ->orWhereDate('metadata->closing_date', '>=', today());
            });

        $categories = (clone $activeJobs)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $companies = (clone $activeJobs)
            ->whereNotNull('metadata->company')
            ->where('metadata->company', '<>', '')
            ->select('metadata->company as company')
            ->distinct()
            ->orderBy('company')
            ->pluck('company');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::in($categories->all())],
            'company' => ['nullable', 'string', Rule::in($companies->all())],
        ]);
        $searchTerm = trim($filters['search'] ?? '');
        $selectedCategory = $filters['category'] ?? '';
        $selectedCompany = $filters['company'] ?? '';

        $jobs = (clone $activeJobs)
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $query) use ($searchTerm): void {
                    $like = '%'.$searchTerm.'%';
                    $query->where('title', 'like', $like)
                        ->orWhere('metadata->position', 'like', $like)
                        ->orWhere('metadata->company', 'like', $like);
                });
            })
            ->when($selectedCategory !== '', fn (Builder $query): Builder => $query->where('category', $selectedCategory))
            ->when($selectedCompany !== '', fn (Builder $query): Builder => $query->where('metadata->company', $selectedCompany))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('pages.bkk.jobs', [
            'jobs' => $jobs,
            'searchTerm' => $searchTerm,
            'jobCategories' => $categories,
            'jobCompanies' => $companies,
            'selectedCategory' => $selectedCategory,
            'selectedCompany' => $selectedCompany,
            'activeJobsCount' => (clone $activeJobs)->count(),
            'partnerCompaniesCount' => $companies->count(),
        ]);
    }

    public function tracerStudy(): View
    {
        return view('pages.bkk.tracer-study');
    }

    public function spmb(SiteConfiguration $configuration): View
    {
        return view('pages.spmb', ['settings' => $configuration->all()]);
    }
}
