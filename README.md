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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Assessment scoring

Assessment forms use the existing `forms.settings` JSON and response `metadata`; no extra database migration is needed. A form can set `settings.assessment.type` to one of:

- `score_bands` for a total score, section/group subtotals and configured inclusive score bands.
- `guna_majority` to count option values into categories and report a winner, tie, or balanced result.
- `prakriti_vpk` to report category counts and percentages, applying configured balance and dual-category tolerances.
- `prakriti_ksa` to report balanced, mixed, or dominant categories from configured tolerances.
- `dhatu_sarata` to report each group independently, classify its score (0–2 Avara, 3–4 Madhya, 5–6 Pravara), and optionally record its current condition.

Set scored options on each question and add `assessment_section` or `assessment_group` to the field's JSON settings to enable subtotals. Category-based forms configure `category_map`; category tallies can also be grouped by `assessment_section`. Dhatu condition fields additionally use `assessment_current_condition` and `condition_map`. Each saved response keeps a snapshot of totals and interpretation in `metadata.assessment_result`, which is shown in response details and individual or aggregate CSV/PDF exports. Category-count assessments report classifications and tallies rather than numeric point totals; score columns are reserved for assessments where points are meaningful. The public confirmation page displays results only when `settings.show_score` is enabled.

The seeded Intellectual Health Assessment (Form 7) demonstrates the score-band setup. Score cutoffs and assessment wording should be reviewed by the form owner or a qualified healthcare professional before publishing; these results are screening/self-assessment information and not diagnoses.

The eight questionnaire definitions are seeded by `AssessmentFormsSeeder` (also called by `DatabaseSeeder`). Run `php artisan db:seed` after migrations to create or synchronize those forms. The seeder does not create fictional patient responses.
