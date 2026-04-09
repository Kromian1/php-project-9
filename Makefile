PORT ?= 8000
start:
	DATABASE_URL=postgresql://mok1408:1@localhost:5432/page_analyzer_dev PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public
install:
	composer install
dump:
	composer dump-autoload
lint:
	composer exec --verbose phpcs -- --standard=PSR12 public