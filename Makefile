PORT ?= 8002
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public
install: # установить зависимости
	composer install
dump:
	composer dump-autoload
validate: # Запуск composer validate
	composer validate
lint: # Запуск phpcs
	composer run lint
lint-fix:
	composer run lint-fix
test:
	composer exec --verbose phpunit tests
test-coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-clover build/logs/clover.xml
test-coverage-text:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text

