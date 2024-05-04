## About the project

Starter-11 is a basic multi-user chat project created with [Laravel 11](https://laravel.com). It also serves as API for a [mobile IOS application](https://github.com/alex3493/starter-11).
Chat and message events are published via [Reverb server](https://reverb.laravel.com/), so that all clients are updated automatically.

Features:
- Register user account. All registered users have *user* role. Administrator should be already created when you finish installation (see below).
- Login to user account. User can log in from multiple devices, a dedicated access token will be created for each device.
- Logout from current device.
- Logout from all devices (sign out).
- View and update user profile.
- View currently registered user devices.
- View chat list and create chat.
- Join and leave chats.
- Enter joined chat, view and add chat messages. *Administrator can enter in all chats*.
- Delete own messages. *Administrator can edit and delete all messages*.
- Edit own chat topic. *Administrator can edit all chats*.
- Delete own chats. *Administrator can delete all chats*.

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
- Run tests: $> ./vendor/bin/sail phpunit
- Open http://localhost in your browser.
- Login as Admin (see credentials below) or register a new user.

Admin user credentials: 
- Email: admin@starter.loc
- Password: password

## Next steps

You are free to use and modify project code.

You can run $> ./vendor/bin/sail npm run dev and all frontend code changes will be reflected in your browser.

You can also save some typing making an [alias](https://laravel.com/docs/11.x/sail#configuring-a-shell-alias) to sail.



