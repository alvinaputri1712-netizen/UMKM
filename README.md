<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Admin roles

The admin panel does not provide public self-registration. Accounts are created by the Superadmin from **Manajemen Admin**:

- **Superadmin** has full access and manages Admin and Editor accounts. Only one Superadmin is allowed.
- **Admin** manages content, website settings, and the media library.
- **Editor** manages website content and can upload images for content, but cannot access settings, the media library, or admin accounts.

For local/testing, when no admin account exists, the login page offers a one-time **Pendaftaran Superadmin pertama** link. After registration, the form closes and the new account is signed in. In production, create the initial Superadmin with `php artisan admin:create` instead. Once a Superadmin exists, the command only offers Admin and Editor roles; additional accounts should be created in the panel.

## SEO, Search Console, and Analytics setup

This project includes dynamic meta tags, canonical URLs, OG/Twitter metadata, sitemap generation, and structured data for public pages. For production deployment, set the base domain in `APP_URL` and optionally the following values in `.env`:

```env
APP_URL=https://www.smkislamiyahciputat.sch.id
GOOGLE_SITE_VERIFICATION=your-google-site-verification-code
GA_MEASUREMENT_ID=G-XXXXXXXXXX
```

### Google Search Console

1. Add a new Domain property in Google Search Console.
2. Copy the TXT verification value from Google.
3. Set it as `GOOGLE_SITE_VERIFICATION` in the production `.env` file.
4. Add the TXT record in your Rumahweb DNS settings.
5. Verify the domain and submit the production `https://www.smkislamiyahciputat.sch.id/sitemap.xml` URL.
6. Monitor indexing and coverage reports.

### Google Analytics 4

1. Create or open a Google Analytics account and add a web data stream.
2. Copy the GA4 Measurement ID (for example `G-XXXXXXXXXX`).
3. Add it as `GA_MEASUREMENT_ID` in the production `.env` file.
4. Keep the tracking code enabled only when the ID is set; the layout will not emit the script otherwise.
5. Validate traffic in the Realtime and DebugView reports after the site is live.

### Robots and sitemap

- The site exposes `/sitemap.xml` for public pages and published content.
- `/adminsmkiciputat` and the login routes are blocked from crawling.
- `/robots.txt` is generated from the application configuration and the production base URL.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
