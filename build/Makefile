php_71:
	docker build --build-arg VERSION=7.1 --tag pawaclawczyk/php:7.1 --file php.dockerfile .
	docker build --build-arg VERSION=7.1 --tag pawaclawczyk/php:latest --file php.dockerfile .

php_72:
	docker build --build-arg VERSION=rc --tag pawaclawczyk/php:7.2 --file php.dockerfile .

push:
	docker login
	docker push pawaclawczyk/php

all: php_71 php_72 push
