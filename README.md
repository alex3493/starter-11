## About the project

Starter-11 is a basic multi-user chat project created with [Laravel 11](https://laravel.com). It also serves as API for a mobile IOS application.
Chat and message events are published via [Reverb server](https://reverb.laravel.com/), so that all clients are updated automatically.

TBC...

## How to install

- Make sure you have [Docker](https://www.docker.com/products/docker-desktop/) installed and running.
- Make sure you have [composer](https://getcomposer.org/) installed.
- Clone the project and cd to project folder.
- $> cp .env.example .env
- $> composer install
- $> ./vendor/bin/sail up -d
- $> ./vendor/bin/sail php artisan migrate
- $> ./vendor/bin/sail php artisan db:seed --class=AdminUserSeeder
- $> ./vendor/bin/sail npm install
- $> ./vendor/bin/sail npm run build
- Open http://localhost in your browser.
- Login as Admin (see credentials below) or register a new user.

Admin user credentials: 
- Email: admin@starter.loc
- Password: password

## Next steps

You are free to use and modify project code.

You can run $> ./vendor/bin/sail npm run dev and all frontend code changes will be reflected in your browser.

You can also save some typing making an [alias](https://laravel.com/docs/11.x/sail#configuring-a-shell-alias) to sail.



