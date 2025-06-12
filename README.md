# Tokodaring
It is an online shopping system or e-commerce platform designed for B2G (Business-to-Government) transactions.

Framework: [Symfony 4.4](https://symfony.com/)

Admin Theme: [AdminLTE](https://adminlte.io/)

## Requirements

- PHP >= 7.1.3
- mysql
- Symfony binary (download [here](https://symfony.com/download))
- Composer (download [here](https://getcomposer.org/download/))
- Node.js (v10.23.0) and NPM (v6.14.8) (download [here](https://nodejs.org/en/download/))

## Installations:

- Install dependencies using `composer install`
- Copy `.env.dist` into `.env` if it is not created automatically, and fill missing details (db, mail, config, etc)
- These directories need recursive permission/ownership from the web server: `var` and `public/uploads`
- Generate application secret using `php bin/console app:generate-app-secret ` and fill `APP_SECRET` key in `.env` file
- Run database migrations using `composer db-migrate`
- Start the local server using `composer start` and access `127.0.0.1:8100` in your browser
- Stop the local server using `composer stop`

NB.
- Database commands (generate & migrate) need to be re-run when adding new entity or updating the existing one(s)
- Please always run `composer analyse` to check the code using PHPStan
- Please follow coding standard from Symfony [here](https://symfony.com/doc/current/contributing/code/standards.html)
- Ignore this error(s) from PHPStan:
    - Controller/Order/CartController.php [`Result of && is always true.`]
    - Controller/Order/OrderController.php  [`Comparison operation ">" between 0 and 0 is always false.`]
    - Controller/Product/AdminProductController.php  [
        `Comparison operation ">" between int<1, max> and 0 is always true.`,
        `Result of && is always true.`
      ]
    - Plugins/AdminPlugin.php [`Comparison operation ">" between 0 and 0 is always false.`]
    - Validator/Constraints/DuplicateSlugValidator.php [`Negated boolean expression is always true.`]

## ISSUE

- [FIXED] DO NOT USE PHP 7.4 at the moment, there's an error when uploading file

## Useful console commands

- To check code using PHPStan: `composer analyse`
- To clear cache in local environment: `composer cc-dev`
- To generate database migration file(s): `composer db-diff`
- To run database migration file(s): `composer db-migrate`
- To create new user:
    - Regular: `php bin/console app:create-user`
    - Administrator: `php bin/console app:create-user --role=ROLE_ADMIN`
    - Super Administrator: `php bin/console app:create-user --role=ROLE_SUPER_ADMIN`

## Assets management using Gulp:

- Install Gulp CLI in global scope (may require permission): `npm install --global gulp-cli`
- Install dependencies using `npm install`
- Run `gulp build` to build all assets (re-run when assets file is added or deleted)
- Run `gulp watch` to watch changes for all assets file (if needed -- re-run if file `bundle.config.js` is modified)
- Run `gulp compile` to compile assets (css & js files only, as defined in `bundle.config.js` file)

## Commands for PRODUCTION environment:

- sudo -Hu www-data php bin/console doctrine:migrations:migrate
- sudo -Hu www-data php bin/console cache:clear --env=prod
- sudo -Hu www-data composer update
- sudo -Hu www-data git checkout .. <file>

NB. If above command(s) executed without sudo, re-apply permission using: `sudo chown www-data:www-data -R var`

## Troubleshoot

- If the web profiler bar (dev environment) is having an unexpected error, try clearing the cache.
- Running `gulp build` might result an error on first try. Run it again, and it will work like a charm.

## Links

- https://github.com/PHPOffice/PhpSpreadsheet
- https://github.com/hanneskod/libmergepdf (version >= 4.0.3 -- use with caution)

## Repository Branch
- The staging server uses the staging branch.
- The production server uses the prod-new branch.

## Server 

# Schema
┌────────────────────────────────────┐    ┌──────────┐
│                                    │--> │          │
│           WEB APPLICATION          │<-- │ Storage  │
└────────────────────────────────────┘    └──────────┘
# staging
- The project directory is located at /var/www/html/tokodaring/.
- nginx
- php7.4-fpm

# prod
- The project directory is located at /var/www/html/bmall-v2/.
- nginx
- php7.4-fpm

## Note
- The application's storage is hosted on a different server.
- Due to issues with database migration in the application, during the initial project setup, you can use the database from staging server. Any subsequent schema changes, such as adding, modifying, or deleting, must be performed manually on the database.

- If there is any confusion about filling in the .env file, you can refer to the .env file located on the staging server. (for development).