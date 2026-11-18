@extends('admin.layout')

@section('title', 'Pengaturan Website')
@section('page-title', 'Pengaturan Website')
@section('breadcrumb', 'Pengaturan Website')

@section('content')
    <div class="admin-page-intro">
        <div>
            <h2>Atur tampilan dan informasi publik</h2>
            <p>Perubahan akan langsung digunakan oleh halaman website.</p>
        </div>
    </div>

    <div class="admin-settings-layout">
        <nav class="admin-settings-nav" aria-label="Kategori pengaturan">
            @foreach ($sections as $sectionKey => $sectionLabel)
                <a href="{{ route('admin.settings.index', ['section' => $sectionKey]) }}" class="{{ $section === $sectionKey ? 'is-active' : '' }}">{{ $sectionLabel }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="admin-card admin-form admin-settings-form" data-media-upload-base="{{ route('admin.media.uploads.store') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="{{ $section }}">

            <div class="admin-card-heading">
                <div><h2>{{ $sections[$section] }}</h2><p>Isi bagian yang ingin diubah, lalu simpan.</p></div>
            </div>

            @php
                $aboutFieldFallbacks = [
                    'about.profile_heading' => $values['identity.school_name'] ?? '',
                    'about.profile_paragraph_1' => $values['home.about.paragraph_1'] ?? '',
                    'about.profile_paragraph_2' => $values['home.about.paragraph_2'] ?? '',
                    'about.profile_image_alt' => $values['home.about.image_alt'] ?? 'Bangunan sekolah SMK Islamiyah Ciputat',
                    'about.location_address' => filled($values['identity.address'] ?? null)
                        ? $values['identity.address']
                        : ($values['identity.location'] ?? ''),
                ];
            @endphp

            <div class="admin-form-grid">
                @foreach ($fields as $key => $field)
                    @php
                        $fieldName = \Illuminate\Support\Str::after($key, $section.'.');
                        $inputName = 'settings['.$section.']['.str_replace('.', '][', $fieldName).']';
                        $settingPath = 'settings.'.$key;
                        $value = old('settings.'.$key, filled($values[$key] ?? null)
                            ? $values[$key]
                            : ($aboutFieldFallbacks[$key] ?? ($values[$key] ?? null)));
                    @endphp
                    <div class="admin-field {{ in_array($field['type'], ['textarea', 'image', 'video_source'], true) ? 'admin-field-full' : '' }}">
                        @if ($field['type'] === 'checkbox')
                            <input type="hidden" name="{{ $inputName }}" value="0">
                            <label class="admin-checkbox-label">
                                <input type="checkbox" name="{{ $inputName }}" value="1" @checked((bool) $value)>
                                <span>{{ $field['label'] }}</span>
                            </label>
                        @elseif ($field['type'] === 'video_source')
                            @php
                                $videoValue = is_string($value) ? $value : '';
                                $currentAsset = $videoAssets->first(fn (\App\Models\MediaAsset $asset): bool => $asset->publicPath() === $videoValue);
                                $youtubeId = \App\Support\VideoSource::youtubeId($videoValue);
                                $source = old($settingPath.'.source', $currentAsset
                                    ? 'media'
                                    : ($videoValue === '' ? 'none' : (\App\Support\VideoSource::supportsUrl($videoValue) ? 'url' : 'existing')));
                                $videoUrl = old($settingPath.'.url', $youtubeId && ! str_starts_with($videoValue, 'http')
                                    ? 'https://youtu.be/'.$youtubeId
                                    : ($source === 'url' ? $videoValue : ''));
                                $assetId = old($settingPath.'.asset_id', $currentAsset?->id);
                            @endphp
                            <label for="setting-{{ str_replace('.', '-', $key) }}-source">{{ $field['label'] }}</label>
                            <select
                                id="setting-{{ str_replace('.', '-', $key) }}-source"
                                name="{{ $inputName }}[source]"
                                data-media-direct-video-source
                                data-media-direct-video-source-for="setting-{{ str_replace('.', '-', $key) }}-file"
                                data-media-direct-video-has-existing="{{ $source === 'media' ? 'true' : 'false' }}"
                            >
                                <option value="none" @selected($source === 'none')>Tidak ditampilkan</option>
                                <option value="media" @selected($source === 'media') {{ $source !== 'media' ? 'hidden' : '' }}>Pertahankan video tersimpan</option>
                                @if ($source === 'existing')
                                    <option value="existing" selected>Pertahankan video saat ini</option>
                                @endif
                                <option value="upload" @selected($source === 'upload')>Pilih video dari perangkat</option>
                                <option value="url" @selected($source === 'url')>Gunakan URL video</option>
                            </select>
                            <input type="hidden" name="{{ $inputName }}[asset_id]" value="{{ $assetId }}" data-media-direct-video-asset data-media-direct-video-asset-for="setting-{{ str_replace('.', '-', $key) }}-file" {{ $source !== 'media' ? 'disabled' : '' }}>
                            <div data-media-direct-video-url-control {{ $source !== 'url' ? 'hidden' : '' }}>
                                <label for="setting-{{ str_replace('.', '-', $key) }}-url">URL YouTube atau tautan langsung MP4/WebM/OGG</label>
                                <input
                                    id="setting-{{ str_replace('.', '-', $key) }}-url"
                                    name="{{ $inputName }}[url]"
                                    type="url"
                                    value="{{ $videoUrl }}"
                                    placeholder="https://youtu.be/... atau https://.../video.mp4"
                                    {{ $source !== 'url' ? 'disabled' : '' }}
                                    {{ $source === 'url' ? 'required' : '' }}
                                >
                            </div>
                            <div class="admin-field admin-field-full" data-media-direct-video-file-control {{ $source !== 'upload' ? 'hidden' : '' }}>
                                <label for="setting-{{ str_replace('.', '-', $key) }}-file">Pilih video dari perangkat</label>
                                <input
                                    id="setting-{{ str_replace('.', '-', $key) }}-file"
                                    name="video_uploads[{{ $section }}][{{ $fieldName }}]"
                                    type="file"
                                    accept="video/mp4,video/webm,video/ogg,.mp4,.m4v,.webm,.ogv,.ogg"
                                    data-media-direct-video
                                    data-media-file
                                    data-media-category="general"
                                    {{ $source !== 'upload' ? 'disabled' : '' }}
                                    {{ $source === 'upload' ? 'required' : '' }}
                                >
                                <div class="admin-media-upload-preview" data-media-preview hidden>
                                    <video controls preload="metadata" data-media-video-preview hidden></video>
                                </div>
                                <small data-media-direct-video-file-details data-media-file-details></small>
                                @error('video_uploads.'.$section.'.'.$fieldName)<small class="admin-field-error">{{ $message }}</small>@enderror
                            </div>
                            <div class="admin-media-status" role="status" data-media-direct-video-status></div>
                            <div class="admin-media-progress" data-media-direct-video-progress hidden>
                                <div><span data-media-direct-video-progress-status>Menyiapkan unggahan...</span><strong data-media-direct-video-progress-value>0%</strong></div>
                                <progress max="100" value="0" data-media-direct-video-progress-bar></progress>
                                <button type="button" class="admin-button admin-button-secondary" data-media-direct-video-cancel>Batalkan</button>
                            </div>
                            <small>Sumber video dapat dipilih dari perangkat atau menggunakan URL. Video lama tetap tersimpan sampai diganti.</small>
                        @else
                            <label for="setting-{{ str_replace('.', '-', $key) }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'textarea')
                                <textarea id="setting-{{ str_replace('.', '-', $key) }}" name="{{ $inputName }}" rows="4">{{ $value }}</textarea>
                            @elseif ($field['type'] === 'image')
                                @php
                                    $category = in_array(\Illuminate\Support\Str::before($fieldName, '.'), ['hero', 'news', 'program', 'gallery', 'teacher'], true)
                                        ? \Illuminate\Support\Str::before($fieldName, '.')
                                        : 'general';
                                @endphp
                                @include('admin.media._upload-field', [
                                    'id' => 'setting-'.str_replace('.', '-', $key),
                                    'inputName' => 'uploads['.$section.']['.str_replace('.', '][', $fieldName).']',
                                    'sourceName' => 'source_uploads['.$section.']['.str_replace('.', '][', $fieldName).']',
                                    'assetInputName' => 'media_asset_ids['.$section.']['.str_replace('.', '][', $fieldName).']',
                                    'category' => $category,
                                    'existingPath' => $value,
                                    'label' => $field['label'],
                                    'errorKey' => 'uploads.'.$key,
                                    'embedded' => true,
                                ])
                                @if ($value)
                                    <label class="admin-checkbox-label admin-remove-image">
                                        <input type="checkbox" name="remove_images[{{ $section }}][{{ str_replace('.', '][', $fieldName) }}]" value="1">
                                        <span>Hapus gambar saat ini</span>
                                    </label>
                                @endif
                            @else
                                <input id="setting-{{ str_replace('.', '-', $key) }}" name="{{ $inputName }}" type="{{ in_array($field['type'], ['link', 'youtube', 'coordinates'], true) ? 'text' : $field['type'] }}" value="{{ $value }}" @if ($field['type'] === 'link') inputmode="url" placeholder="/path atau https://..." @elseif ($field['type'] === 'youtube') placeholder="ID video YouTube 11 karakter" @elseif ($field['type'] === 'coordinates') inputmode="decimal" placeholder="Contoh: -6.2, 106.7" @endif>
                            @endif
                        @endif
                        @error('settings.'.$key)<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('settings.'.$key.'.source')<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('settings.'.$key.'.url')<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('settings.'.$key.'.asset_id')<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('uploads.'.$key)<small class="admin-field-error">{{ $message }}</small>@enderror
                    </div>
                @endforeach
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button admin-button-primary" data-settings-submit>
                    <span data-settings-submit-label>Simpan pengaturan</span>
                </button>
            </div>
        </form>
    </div>
@endsection
