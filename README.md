# kawf

kawf is a web forum written in PHP.

See `docs/INSTALL.txt` for install instructions.

If you are migrating from an older version of kawf, please read
`docs/schema-migration.txt`

# docker

## docker build
You need to build kawf first before you can run docker-compose (or just docker run). The build uses environment variables, first create that file (not in the repository):
```
cp config/sample-envvars config/envvars
```
Edit to taste.

Install docker and docker compose.

Now you can build the container and initialize the mysql db:
```
make docker-build
make docker-init
```

## docker-compose

Start up kawf:
```
docker-compose up -d
```
* from a browser, head to `http://localhost/` and follow `docs/INSTALL.txt` and skip to:
```
  * Try to hit "http://site/create.phtml" where "site" is the domain where "site"
    is the domain you set up in setup-local.inc. Make sure it matches your DNS
    setup, or nothing will work right.
```
* when you are done, run `$ docker-compose down`

## docker run
If you just want to run kawf container locally, after building it

```
docker run -it -p 0.0.0.0:80:80 --env-file=config/envvars kawf:latest`
```
