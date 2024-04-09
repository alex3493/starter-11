## About the project

Starter-11 is a basic multi-user chat project created in Laravel 11. It also serves as API for a mobile IOS application.
All chat and message events are published via Reverb server (Pusher protocol), so that all clients are updated automatically.

TBC...

## How to install

- Make sure you have Docker installed and running.
- Clone the project and cd to project folder.
- $> cp .env.example .env
- $> composer install
- $> npm install
- $> ./vendor/bin/sail up -d
- $> ./vendor/bin/sail php artisan migrate
- $> ./vendor/bin/sail php artisan db:seed --class=AdminUserSeeder
- $> ./vendor/bin/sail npm run build
- Open http://localhost in your browser.

Admin user credentials: 
- Email: admin@starter.loc
- Password: password

