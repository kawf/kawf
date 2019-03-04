# kawf

kawf is a web forum written in PHP.

See `docs/INSTALL.txt` for install instructions.

If you are migrating from an older version of kawf, please read
`docs/schema-migration.txt`

# docker

## docker build
you need to build kawf first before you can run docker-compose (or just docker run).  The build uses environment variables, first create that file (not in the repository):

ENVVAR file:
```
  APACHE_SERVER_NAME=kawf.org
  APACHE_DOCROOT=/var/www/html/config
  APACHE_SERVERALIAS=forums.kawf.org
  DB_HOST=db
  RO_USERNAME=""
  RO_PASSWORD=""
  MYSQL_ROOT_PASSWORD=password
  MYSQL_DATABASE=kawf
  MYSQL_USER=kawf
  MYSQL_PASSWORD=password
  BOUNCE_HOST=bounce.kawf.org
  COOKIE_HOST=.kawf.org
  DOMAIN=kawf.org
```

Now you can build the container:
* run `$ docker build --build-arg BUILD_TYPE=kawf -t kawf .`

## docker-compose
Install docker and if necessary (i.e. did not come with docker install) docker-compose on your OS.  The docker-compose setup will run mysql as well as kawf so you can get a working development environment.

* go to repository root
* make sure the envvar file paths matches your location from above in `docker-compose.yaml`
```
  env_file:
    - /path/to/envvars
```
* run `$ docker-compose up -d`
* run `$ docker ps` to get the ID of the kawf:latest
* run `$ docker exec -it <CONTAINER_ID> /bin/bash` to SSH to the kawf container
* run `$ /var/www/html/docker/kawf.sh` to setup MySQL for kawf (replaces setup-db.sh)
* run `$ php /var/www/html/tools/initial.php` but note that this threw an error
* from a browser, head to `http://localhost/` and follow `docs/INSTALL.txt` and skip to:
```
  * Try to hit "http://site/create.phtml" where "site" is the domain where "site"
    is the domain you set up in setup-local.inc. Make sure it matches your DNS
    setup, or nothing will work right.
```
* when you are done, run `$ docker-compose down`

## docker run
If you just want to run kawf container locally, after building it

* run `$ docker run -it -p 0.0.0.0:80:80 --env-file=/path/to/envvars kawf:latest`
