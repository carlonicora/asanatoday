docker-compose --project-directory .docker/ -f .docker/docker-compose.yml down
git pull

if [[ " $@ " =~ " -b " ]]; then
   docker-compose --project-directory .docker/ -f .docker/docker-compose.yml build
fi


docker-compose --project-directory .docker/ -f .docker/docker-compose.yml up -d
docker exec -ti asanatoday composer install --no-dev
rm -rf cache/*
docker-compose --project-directory .docker/ -f .docker/docker-compose.yml down
docker-compose --project-directory .docker/ -f .docker/docker-compose.yml up -d