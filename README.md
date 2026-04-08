# WeatherHub

WeatherHub is a midterm-ready PHP and MySQL project for IT223. It includes working login and registration, session authentication, protected pages, a clean folder structure, and a normalized database designed for future weather features.

## Features
- User registration with validation
- Secure login with password hashing
- Session-based authentication
- Protected dashboard
- Weather search module scaffold
- Saved locations
- Search history
- User preferences
- Original custom UI design

## Folder Structure
- `app/` - controllers, models, middleware, helpers, config
- `public/` - public pages and assets
- `resources/views/` - shared layouts and partials
- `database/` - SQL schema
- `docs/` - use case notes

## How to Run
1. Copy the `weatherhub` folder into `htdocs`.
2. Start Apache and MySQL in XAMPP.
3. Create a database named `weatherhub` in phpMyAdmin.
4. Import `database/weatherhub.sql`.
5. If your MySQL credentials are different, update `app/config/database.php`.
6. Open `http://localhost/weatherhub/public/` or `http://localhost:8080/weatherhub/public/` depending on your Apache port.

## Notes
- For the midterm, the weather module uses sample output and stores search activity.
- For the final phase, replace the sample weather output with real API integration.
