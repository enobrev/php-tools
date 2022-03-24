MY_USER_ID=$(shell id -u)
MY_GROUP_ID=$(shell id -g)
CONTAINER=enobrev-php-tools

.PHONY: build
build:
	docker build -t enobrev-php-tools .

.PHONY: composer-install
composer-install:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api $(CONTAINER) composer install

.PHONY: composer-update
composer-update:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api $(CONTAINER) composer update

.PHONY: composer-upgrade
composer-upgrade:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api $(CONTAINER) composer upgrade

.PHONY: docker-test
docker-test:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api $(CONTAINER) vendor/bin/phpunit tests

.PHONY: test
test:
	./vendor/bin/phpunit --stop-on-failure tests