<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## EV Charging Management System

This is a Laravel-based application for managing electric vehicle charging infrastructure throughout Indonesia. The system allows users to:

- View and filter charging locations across different providers
- Access information about EV charging stations from various providers including PLN
- Track charging session records with detailed cost breakdowns
- View provider details and contact information

## Key Features

1. **Location Management**
   - Interactive map view of charging locations
   - Filtering by province, city, charging type, and power specifications
   - Integration with PLN charging locations (public utility provider)

2. **Provider Management**
   - Registration and management of charging service providers
   - Public and private provider access controls
   - Provider-specific details, pricing, and contact information

3. **Charging Session Tracking**
   - Detailed logging of charging sessions
   - Cost calculations including taxes and admin fees
   - Before/after kilometer tracking
   - kWh consumption measurement

## System Architecture

The application follows the Laravel MVC architecture with the following components:

- **Frontend**: Blade templates with Tailwind CSS styling
- **Backend**: Laravel PHP framework with MySQL database
- **Maps**: Interactive mapping functionality using OpenStreetMap (free alternative to Google Maps)
- **Authentication**: Laravel Sanctum API authentication
- **File Storage**: Image uploads for charging session verification

## Deployment

To build and deploy the application assets to GitHub:

1. Run `npm run build` to compile the assets
2. Run `npm run deploy` to automatically commit and push built files to GitHub

Alternatively, you can manually run the deploy script:
```bash
./deploy.sh
```

The system also includes GitHub Actions for automatic deployment to GitHub Pages when pushing to the main branch.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).