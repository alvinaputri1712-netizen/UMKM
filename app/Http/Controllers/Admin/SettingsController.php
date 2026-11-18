<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use App\Support\MediaLibrary;
use App\Support\PublicImageStorage;
use App\Support\SiteConfiguration;
use App\Support\VideoSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request, SiteConfiguration $configuration): View
    {
        $sections = config('site-content.sections');
        $section = $request->query('section', 'identity');

        if (! array_key_exists($section, $sections)) {
            $section = 'identity';
        }

        $fields = collect(config('site-content.fields'))
            ->filter(fn (array $field): bool => $field['section'] === $section);

        return view('admin.settings.index', [
            'section' => $section,
            'sections' => $sections,
            'fields' => $fields,
            'values' => $configuration->all(),
            'videoAssets' => MediaAsset::query()
                ->where('media_type', 'video')
                ->latest('id')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        SiteConfiguration $configuration,
        PublicImageStorage $images,
        MediaLibrary $mediaLibrary,
    ): RedirectResponse {
        $sections = config('site-content.sections');
        $validated = $request->validate([
            'section' => ['required', 'string', 'in:'.implode(',', array_keys($sections))],
        ]);
        $section = $validated['section'];
        $fields = collect(config('site-content.fields'))
            ->filter(fn (array $field): bool => $field['section'] === $section);
        $rules = [];

        foreach ($fields as $key => $field) {
            $fieldName = Str::after($key, $section.'.');
            $settingPath = 'settings.'.$section.'.'.$fieldName;

            if ($field['type'] === 'video_source') {
                $sourcePath = $settingPath.'.source';
                $rules[$sourcePath] = ['sometimes', Rule::in(['none', 'media', 'url', 'upload', 'existing'])];
                $rules[$settingPath.'.url'] = [
                    'exclude_unless:'.$sourcePath.',url',
                    'required',
                    'url',
                    'regex:/^https?:\/\/\S+$/i',
                    'max:2048',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! is_string($value) || ! VideoSource::supportsUrl($value)) {
                            $fail('Masukkan URL YouTube atau tautan langsung file MP4, WebM, atau OGG.');
                        }
                    },
                ];
                $rules[$settingPath.'.asset_id'] = [
                    'exclude_unless:'.$sourcePath.',media',
                    'required',
                    'integer',
                    Rule::exists('media_assets', 'id')->where(
                        fn ($query) => $query->where('media_type', 'video'),
                    ),
                ];
                $rules['video_uploads.'.$section.'.'.$fieldName] = [
                    'exclude_unless:'.$sourcePath.',upload',
                    'required',
                    'file',
                    'mimetypes:video/mp4,video/webm,video/ogg',
                    'extensions:mp4,m4v,webm,ogv,ogg',
                    'max:'.(config('media.max_upload_mb') * 1024),
                ];

                continue;
            }

            $rules['settings.'.$section.'.'.$fieldName] = match ($field['type']) {
                'checkbox' => ['sometimes', 'boolean'],
                'number' => ['nullable', 'integer', 'between:-1000,1000'],
                'url' => ['nullable', 'url', 'regex:/^https?:\/\/\S+$/i', 'max:2048'],
                'link' => ['nullable', 'string', 'regex:/^(https?:\/\/\S+|\/(?!\/)\S*)$/i', 'max:2048'],
                'coordinates' => [
                    'nullable',
                    'string',
                    'max:100',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! is_string($value) || trim($value) === '') {
                            return;
                        }

                        $coordinates = array_map('trim', explode(',', $value));

                        if (count($coordinates) !== 2 || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1])) {
                            $fail('Masukkan koordinat dengan format lintang, bujur.');

                            return;
                        }

                        $latitude = (float) $coordinates[0];
                        $longitude = (float) $coordinates[1];

                        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                            $fail('Lintang harus antara -90 dan 90, dan bujur antara -180 dan 180.');
                        }
                    },
                ],
                'youtube' => ['nullable', 'regex:/^[A-Za-z0-9_-]{11}$/'],
                'email' => ['nullable', 'email', 'max:255'],
                'tel', 'text' => ['nullable', 'string', 'max:500'],
                'textarea' => ['nullable', 'string', 'max:20000'],
                'image' => ['nullable'],
                default => ['nullable', 'string', 'max:500'],
            };

            if ($field['type'] === 'image') {
                $rules['uploads.'.$section.'.'.$fieldName] = [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp,avif',
                    'extensions:jpg,jpeg,png,webp,avif',
                    'max:'.(config('media.max_upload_mb') * 1024),
                ];
                $rules['source_uploads.'.$section.'.'.$fieldName] = [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp,avif',
                    'extensions:jpg,jpeg,png,webp,avif',
                    'max:'.(config('media.max_upload_mb') * 1024),
                ];
                $rules['media_asset_ids.'.$section.'.'.$fieldName] = [
                    'nullable',
                    'integer',
                    'exists:media_assets,id',
                ];
                $rules['remove_images.'.$section.'.'.$fieldName] = ['sometimes', 'boolean'];
            }
        }

        $validatedSettings = $request->validate($rules);
        $currentValues = $configuration->all();
        $oldImages = [];

        foreach ($fields as $key => $field) {
            $fieldName = Str::after($key, $section.'.');
            $oldValue = $currentValues[$key] ?? null;

            if ($field['type'] === 'video_source') {
                $source = Arr::get($validatedSettings, 'settings.'.$section.'.'.$fieldName.'.source');

                if ($source === null || $source === 'existing') {
                    continue;
                }

                $value = match ($source) {
                    'media' => $images->storeFromAsset(
                        (int) Arr::get($validatedSettings, 'settings.'.$section.'.'.$fieldName.'.asset_id'),
                        'video',
                    ),
                    'url' => trim(Arr::get($validatedSettings, 'settings.'.$section.'.'.$fieldName.'.url')),
                    'upload' => $mediaLibrary->storeVideo(
                        $request->file('video_uploads.'.$section.'.'.$fieldName),
                        'general',
                        'original',
                        Auth::guard('admin')->id(),
                    )->publicPath(),
                    default => null,
                };
            } elseif ($field['type'] === 'image') {
                $newFile = $request->file('uploads.'.$section.'.'.$fieldName);
                $assetId = $request->input('media_asset_ids.'.$section.'.'.$fieldName);
                $removeImage = $request->boolean('remove_images.'.$section.'.'.$fieldName);

                if ($newFile !== null) {
                    $category = Str::before($fieldName, '.');
                    $category = in_array($category, ['hero', 'news', 'program', 'gallery', 'teacher'], true)
                        ? $category
                        : 'general';
                    $value = $images->store(
                        $newFile,
                        'cms/settings',
                        $category,
                        $request->file('source_uploads.'.$section.'.'.$fieldName),
                        Auth::guard('admin')->id(),
                    );
                } elseif (filled($assetId)) {
                    $value = $images->storeFromAsset((int) $assetId);
                } elseif ($removeImage) {
                    $value = null;
                } else {
                    continue;
                }

                if (filled($oldValue)) {
                    $oldImages[] = $oldValue;
                }
            } elseif ($field['type'] === 'checkbox') {
                $value = $request->boolean('settings.'.$section.'.'.$fieldName);
            } else {
                $value = Arr::get($validatedSettings, 'settings.'.$section.'.'.$fieldName);
            }

            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $section,
                    'value' => json_encode($value, JSON_THROW_ON_ERROR),
                ],
            );
        }

        foreach ($oldImages as $oldImage) {
            $images->deleteIfUnused($oldImage);
        }

        $configuration->forgetCache();

        return redirect()
            ->route('admin.settings.index', ['section' => $section])
            ->with('success', 'Pengaturan berhasil disimpan.');
    }
}
