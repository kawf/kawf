BUILD_TYPE?=kawf

.PHONY: all docker-build docker-init
all:
	@echo "usage: 'make docker-build' or 'make docker-init'"

docker-build:
	BUILD_TYPE=${BUILD_TYPE} docker-compose -f docker-compose.yaml -f docker/docker-compose-tasks.yaml build

docker-init:
	BUILD_TYPE=${BUILD_TYPE} docker-compose -f docker-compose.yaml -f docker/docker-compose-tasks.yaml run kawf-init

docker-up:
	docker-compose up -d && docker-compose logs -f
