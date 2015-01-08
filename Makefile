
test:
	./vendor/bin/phpunit

init:
	composer install

dist:
	composer archive

.PHONY: test init dist

