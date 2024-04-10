# Edenred Synchronizer

API service to interact with Edenred FTP Server for merchants synchronization

## Start server

This project uses [Symfony Docker](https://github.com/dunglas/symfony-docker)

```shellsession
docker compose build --no-cache --pull
docker compose up
```

The uploaded files can be found in `var/upload`.
The files to download can be found in `var/download`.

## Create a new api client

```shellsession
docker compose exec php bin/console synchronizer:client:create "client_name"
```

This command will give to you an api key that should be used on each call as a query param

```
api/merchants/{siretId}?api_key=<api_key>
```

## Command to read files from Edenred SFTP

```shellsession
docker compose exec php bin/console edenred:synchronizer:read
```

## API docs

https://localhost/api

## Testing

Sending

```shellsession
curl -k -v -X POST -H 'Content-Type: application/json' -d '{"merchants":[{"siret":"123456"}]}' 'https://localhost/api/merchants?api_key=<api_key>'
```
