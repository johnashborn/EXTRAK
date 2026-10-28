# Laragon PHP Project Guidelines

## Project Environment
- Stack: PHP, Laragon, Composer, MySQL
- Base Directory: C:\laragon\www\your-project-folder

## Common Commands
- Run Migrations: `php artisan migrate` (if Laravel)
- Run Tests: `php artisan test` or `vendor/bin/phpunit`
- Clear Cache: `php artisan config:clear && php artisan cache:clear`

## Code Style & Conventions
- Language: PHP 8.x+ 
- Style: Follow PSR-12 coding standards.
- Database: Use Eloquent/PDO strictly; avoid writing raw SQL strings unless optimizing.
- Architecture: Keep controllers thin. Put business logic in Services or Actions.
