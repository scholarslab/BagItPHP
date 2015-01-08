
test:
	./vendor/bin/phpunit

init:
	composer install

dist:
	composer archive --format=zip

.PHONY: test init dist

