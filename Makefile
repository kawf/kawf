BUILD_TYPE?=kawf

.PHONY: all test docker-build docker-init docker-update
all:
	@echo "usage: 'make test', 'make docker-build,' 'make docker-init,' or 'make docker-update'"

test:
	phpunit --cache-directory .phpunit.cache test/unit

docker-build:
	BUILD_TYPE=${BUILD_TYPE} docker-compose -f docker-compose.yaml -f docker/docker-compose-tasks.yaml build

docker-init:
	BUILD_TYPE=${BUILD_TYPE} docker-compose -f docker-compose.yaml -f docker/docker-compose-tasks.yaml run kawf-init

docker-up:
	docker-compose up -d && docker-compose logs -f

docker-update: docker-down docker-build docker-up

docker-down:
	docker-compose down
