# Innoscripta-backend

## clone repo
git clone https://github.com/conradsuuna/innoscripta-backend.git

## add .env file
go into the project directory and `touch .env` to create an environment file

copy contents from .ENVEXAMPLE and paste them in .env and modify accordingly

## Dependencies
run `composer install` to install dependencies

## DB
create a mysql db with a name of your choice

run migrations `php artisan migrate` to commit db changes

## run project
run `php artisan serve` to run project
