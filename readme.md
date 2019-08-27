# FAS - Facility Accounting System 

## Dependencies
You need:
- Docker
- IDE of your choice

## Installation

- Clone the repository
- Install Docker and Docker-Compose (see https://docs.docker.com/install/)

## Startup

- run `docker-compose -f docker-compose.local.yml up -d`
- run `docker exec fas-core-dev composer install`
- run `docker exec fas-core-dev php bin/console doctrine:migrations:migrate -n`

## Load fixtures
- run `docker exec fas-core-dev php bin/console doctrine:fixtures:load -n`

## Stop Docker
- run `docker-compose -f docker-compose.local.yml down`

## Running tests

- run `docker exec fas-core-dev bin/phpunit`

## Use the application

The main application is accessible under `localhost:8080`. Postgres database is available under `localhost:5440`. Also there is a PhpPgAdmin under `localhost:5441`.
(user: `fas-dev`, pw: `fas-dev`)

## Development Server

Server can be found under https://dev-fas.bb-c.ch.

## Translation

All translations will be stored under `/translations`.
Use placeholders like `{{'translation.key'|trans }}` for translatable messages in templates.
Run `./bin/console translation:update --dump-messages --force --output-format=po en` to extract all messages from the tempaltes.

## Migration from RAS to FAS

To run the migrations, these tables need to be empty:`account, account_account, account_email, account_facility_role, accounting_position, cost_forecast_week_day, facility, facility_layout, flex_param, person, login_attempt, report, report_position, flex_param_value, report_position_value`

### Migrate a facility with all its users and reports
- `docker exec fas-core-dev php bin/console app:ras-data-migrate {facilityId}`
- To check the migrated facility and create the facility configuration use the migration user. Afterwards, delete the user as admin.
  - Username: `{facility.name}_migration`
  - Password: `Z]C$h8FQ9dPPaC8>W`

### Migrate facility, users, and reports one by one
- Migrate ALL RAS restaurants run: `docker exec fas-core-dev php bin/console app:ras-facility-migrate-all -n`
- Migrate one RAS restaurant by it's ID run: `docker exec fas-core-dev php bin/console app:ras-facility-migrate {Id} -n`
- Migrate ALL RAS users run: `docker exec fas-core-dev php bin/console app:ras-users-migrate`

## Data Base init commands
- `docker exec fas-core-dev php bin/console doctrine:migrations:migrate -n`
- `docker exec fas-core-dev php bin/console app:base-data-insert`
- `docker exec fas-core-dev php bin/console app:add-routines`
- `docker exec fas-core-dev php bin/console doctrine:fixtures:load --append -n`

## Specification document
- https://docs.google.com/document/d/1QnOvvkT0vjOs8LYmLqBn-WHdt8AdtYGyUwpBlu8ZZk4
