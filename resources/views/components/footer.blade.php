@php($logoUrl = $siteConfiguration->imageUrl($siteSettings['identity.logo']))
<footer class="site-footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand footer-column footer-column--brand">
                <a href="{{ route('home') }}" class="brand" aria-label="Beranda">
                    @if ($logoUrl)
                        <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo {{ $siteSettings['identity.school_name'] }}">
                    @endif

                    <span class="brand-text">
                        <strong>{{ mb_strtoupper($siteSettings['identity.school_name']) }}</strong>
                        <span class="brand-text__sub">{{ mb_strtoupper($siteSettings['identity.location']) }}</span>
                    </span>
                </a>

                <p>{{ $siteSettings['footer.description'] }}</p>

                @php($contactLinks = collect([
                    'whatsapp' => [
                        'platform' => 'whatsapp',
                        'label' => 'WhatsApp',
                        'url' => filled($siteSettings['identity.whatsapp']) ? 'https://wa.me/'.preg_replace('/\D+/', '', $siteSettings['identity.whatsapp']) : null,
                        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#25D366" d="M12.04 2c-5.52 0-10 4.48-10 10 0 1.77.46 3.49 1.32 5L2 22l5.15-1.35a9.92 9.92 0 0 0 4.89 1.35h.01c5.52 0 10-4.48 10-10s-4.47-10-10-10Zm0 18.25h-.01a8.26 8.26 0 0 1-4.2-1.15l-.3-.18-3.05.8.81-2.97-.19-.3a8.27 8.27 0 1 1 7.14 3.8Zm4.54-6.19c-.25-.12-1.48-.73-1.71-.82-.23-.08-.39-.12-.56.12-.17.25-.65.82-.8 1-.15.17-.3.2-.55.07-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.24-1.47-1.38-1.72-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.43.13-.14.17-.25.25-.42.08-.17.04-.32-.02-.44-.06-.12-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.42h-.48c-.17 0-.44.06-.67.32-.23.25-.87.86-.87 2.09 0 1.24.89 2.42.99 2.59.11.17 1.72 2.64 4.18 3.7.59.26 1.04.42 1.4.54.59.19 1.12.16 1.54.1.47-.07 1.48-.61 1.69-1.2.21-.59.21-1.09.15-1.2-.07-.11-.24-.17-.5-.29Z"/></svg>',
                    ],
                    'email' => [
                        'platform' => 'email',
                        'label' => 'Email sekolah',
                        'url' => filled($siteSettings['identity.email']) ? 'mailto:'.trim($siteSettings['identity.email']) : null,
                        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#F05A28" d="M3 6.75A2.75 2.75 0 0 1 5.75 4h12.5A2.75 2.75 0 0 1 21 6.75v10.5A2.75 2.75 0 0 1 18.25 20H5.75A2.75 2.75 0 0 1 3 17.25V6.75Zm2.23-.75 7.77 6.21 7.77-6.21H5.23Zm14.52 2.28-7.15 5.72a1.25 1.25 0 0 1-1.2 0L4.25 8.28v9c0 .41.34.75.75.75h13.99c.41 0 .75-.34.75-.75v-9Z"/></svg>',
                    ],
                    'instagram' => [
                        'platform' => 'instagram',
                        'label' => 'Instagram',
                        'url' => $siteSettings['identity.instagram'] ?: null,
                        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><defs><linearGradient id="ig-gradient" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FEDA75"/><stop offset="33%" stop-color="#FA7E1E"/><stop offset="66%" stop-color="#D62976"/><stop offset="100%" stop-color="#962FBF"/></linearGradient></defs><path fill="url(#ig-gradient)" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 0 1 12 7.5Zm0 2A2.5 2.5 0 1 0 14.5 12 2.5 2.5 0 0 0 12 9.5Zm5.25-3.25a1.25 1.25 0 1 1-1.25 1.25 1.25 1.25 0 0 1 1.25-1.25Z"/></svg>',
                    ],
                    'facebook' => [
                        'platform' => 'facebook',
                        'label' => 'Facebook',
                        'url' => $siteSettings['identity.facebook'] ?: null,
                        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#1877F2" d="M13.5 22v-8h2.7l.4-3h-3.1V7.3c0-.8.2-1.5 1.5-1.5H16V2.9c-.3-.1-1.2-.2-2.3-.2-2.4 0-4.1 1.4-4.1 4.3V11H7v3h2.6v8h3.9Z"/></svg>',
                    ],
                    'twitter' => [
                        'platform' => 'twitter',
                        'label' => 'X / Twitter',
                        'url' => $siteSettings['identity.twitter'] ?: null,
                        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#111111" d="M18.9 2h3.68l-8.04 9.19L24 22h-7.41l-5.8-8.5L4.17 22H.49l8.6-9.83L0 2h7.59l5.24 7.78L18.9 2Zm-1.29 18.49h2.04L7.08 3.22H4.92l12.69 17.27Z"/></svg>',
                    ],
                    'youtube' => [
                        'platform' => 'youtube',
                        'label' => 'YouTube',
                        'url' => $siteSettings['identity.youtube'] ?: null,
                        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#FF0000" d="M23.5 6.19a3.02 3.02 0 0 0-2.13-2.14C19.51 3.75 12 3.75 12 3.75s-7.51 0-9.37.3A3.02 3.02 0 0 0 .5 6.19C.2 8.05.2 12 .2 12s0 3.95.3 5.81a3.02 3.02 0 0 0 2.13 2.14c1.86.3 9.37.3 9.37.3s7.51 0 9.37-.3a3.02 3.02 0 0 0 2.13-2.14c.3-1.86.3-5.81.3-5.81s0-3.96-.3-5.82ZM9.75 15.55V8.46L15.5 12l-5.75 3.55Z"/></svg>',
                    ],
                ])->filter(fn (array $link): bool => filled($link['url']))->all())

                @if (! empty($contactLinks))
                    <div class="footer-social" aria-label="Kontak dan media sosial sekolah">
                        @foreach ($contactLinks as $contact)
                            @php($externalLink = str_starts_with($contact['url'], 'http'))
                            <a
                                href="{{ $contact['url'] }}"
                                class="footer-social__link footer-social__link--{{ $contact['platform'] }}"
                                data-platform="{{ $contact['platform'] }}"
                                title="{{ $contact['label'] }}"
                                aria-label="{{ $contact['label'] }}"
                                @if ($externalLink)
                                    target="_blank"
                                    rel="noopener noreferrer"
                                @endif
                            >
                                {!! $contact['svg'] !!}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <nav class="footer-column" aria-label="Navigasi utama footer">
                <h4>Navigasi</h4>
                <a href="{{ route('home') }}" class="footer-link">Beranda</a>
                <a href="{{ route('about') }}" class="footer-link">Tentang Kami</a>
                <a href="{{ route('teachers') }}" class="footer-link">Tenaga Kependidikan</a>
                <a href="{{ route('spmb') }}" class="footer-link">SPMB</a>
                <a href="{{ route('news.index') }}" class="footer-link">Berita</a>
            </nav>

            <nav class="footer-column" aria-label="Program keahlian footer">
                <h4>Program Keahlian</h4>
                @foreach ($navigationPrograms as $program)
                    <a href="{{ route('program.show', $program->slug) }}" class="footer-link">{{ $program->title }}</a>
                @endforeach
            </nav>

            <div class="footer-column footer-column--info" aria-label="Informasi sekolah">
                <h4>Informasi Sekolah</h4>

                @if ($siteSettings['identity.address'])
                    <div class="footer-contact">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a7 7 0 0 1 7 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 0 1 7-7Zm0 9.5A2.5 2.5 0 1 0 12 6a2.5 2.5 0 0 0 0 5.5Z"/></svg>
                        <span>{{ $siteSettings['identity.address'] }}</span>
                    </div>
                @endif

                @if ($siteSettings['identity.whatsapp'])
                    <div class="footer-contact">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.148-.198.297-.767.966-.94 1.164-.173.198-.347.223-.644.074-.297-.149-1.257-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51a12.247 12.247 0 0 0-.57-.01c-.198 0-.52.074-.79.372-.271.297-1.04 1.016-1.04 2.479 0 1.463 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.87.118.571-.085 1.758-.719 2.005-1.414.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347Z"/></svg>
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $siteSettings['identity.whatsapp']) }}" target="_blank" rel="noopener noreferrer">WhatsApp sekolah</a>
                    </div>
                @endif

                @if ($siteSettings['identity.email'])
                    <div class="footer-contact">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 7.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm2.34-.5 6.66 5.34 6.66-5.34H6.34Zm12.16 2.1-5.86 4.71a1.63 1.63 0 0 1-2.12 0L5.5 9.1v7.4c0 .28.22.5.5.5h11c.28 0 .5-.22.5-.5v-7.4Z"/></svg>
                        <a href="mailto:{{ $siteSettings['identity.email'] }}">{{ $siteSettings['identity.email'] }}</a>
                    </div>
                @endif

            </div>
        </div>

        <div class="footer-bottom">
            <span>© {{ date('Y') }} {{ $siteSettings['identity.school_name'] }}. All Rights Reserved.</span>
            <span class="footer-bottom__right">
                <span class="footer-bottom__mark" aria-hidden="true"></span>
                {{ filled($siteSettings['identity.copyright']) ? $siteSettings['identity.copyright'] : 'Website Informasi Sekolah' }}
            </span>
        </div>
    </div>
</footer>
