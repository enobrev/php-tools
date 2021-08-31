MY_USER_ID=$(shell id -u)
MY_GROUP_ID=$(shell id -g)

.PHONY: build
build:
	docker build -t enobrev-php-tools .

.PHONY: composer-install
composer-install:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api enobrev-php-tools composer install

.PHONY: composer-update
composer-update:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api enobrev-php-tools composer update

.PHONY: composer-upgrade
composer-upgrade:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api enobrev-php-tools composer upgrade

.PHONY: test
test:
	docker run -i -t --rm -u $(MY_USER_ID):$(MY_GROUP_ID) -v $(PWD):/usr/src/api enobrev-php-tools vendor/bin/phpunit tests
