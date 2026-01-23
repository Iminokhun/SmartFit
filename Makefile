DC := docker-compose
DCE := $(DC) exec
FPM := $(DCE) gym-app
ARTISAN := $(FPM) php artisan
NODE := $(DCE) gym-node

start:
	@$(DC) up -d
stop:
	@$(DC) down
restart:
	@$(DC) down && $(DC) up -d
build:
	@$(DC) build
rebuild:
	@$(DC) down && $(DC) up -d --build
bash:
	@$(FPM) bash
webserver:
	@$(DC) asarlar-webserver bash
db:
	@$(DCE) asarlar-db bash

composer-install:
	@$(FPM) composer install
composer-update:
	@$(FPM) composer update

npm-install:
	@$(NODE) npm install
npm-dev:
	@$(NODE) npm run dev
gulp:
	@$(NODE) npx gulp build

setup: composer-install npm-install

permissions:
	sudo chown -R $$USER:$$USER ./ && sudo chmod -R 777 ./storage/logs && sudo chmod -R 777 ./storage/framework  && sudo chmod -R 777 ./storage/debugbar  && sudo chmod -R 777 ./bootstrap/cache && sudo chmod -R 777 ./assets

migrate:
	@$(ARTISAN) migrate

migrate-refresh:
	@$(ARTISAN) migrate:refresh

seed:
	@$(ARTISAN) db:seed

seed-faker:
	@$(ARTISAN) db:seed --class=FactorySeeder

langs:
	@$(ARTISAN) lang:generate --type=json --append --langs="en" --langs="ru" --langs="uz"

ide:
	@$(ARTISAN) ide-helper:generate
ide-models:
	@$(ARTISAN) ide-helper:models

clear:
	@$(ARTISAN) optimize:clear

test:
	@$(ARTISAN) test

import-db:
	
