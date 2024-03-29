# edenred-synchronizer

API service to interact with Edenred FTP Server for merchants synchronizaition

## Requirements
```
php8.2
sqlite3
php8.2-sqlite3
php8.2-mbstring
```
## Database setup
```shellsession
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Start server
```shellsession
symfony server:start
```

## Create a new api client
```shellsession
php bin/console synchronizer:client:create "client_name"
```
This command will give to you an api key that should be used on each call as a query param ('ak_' preffix should be added to the api key)
```
api/merchants/{siretId}?api_key=ak_9efcd259266235814829bfb9f2132acac76f0b27
```

## API docs

http://localhost:8000/api

## Testing

This service can be tested using a local SFTP server

You can use this dockerized server https://github.com/emberstack/docker-sftp

### Setting up
Create file `docker-compose.sftp.yml` with the following content at `src/docker-compose`

```yaml
version: '3'
services:
  sftp:
    image: "emberstack/sftp:dev"
    ports:
      - "22:22"
    volumes:
    - ../../samples/sftp.json:/app/config/sftp.json:ro
    - ~.ssh/id_rsa.pub:/home/demo2/.ssh/keys/id_rsa.pub:ro

```

Maybe you have to change the path to your public key `~.ssh/id_rsa.pub`.

Create file `sftp.json` with the following sample content at `samples` folder.

```json
{
    "Global": {
        "Chroot": {
            "Directory": "%h",
            "StartPath": "sftp"
        },
        "Directories": ["sftp"]
    },
    "Users": [
        {
            "Username": "<<your_username>>",
            "PublicKeys": [
                "<<your_public_key>>",
                "ssh-rsa ...... username@0.0.0.0:22"
            ]
        },
        {
            "Username": "demo2",
            "Password": "demo2"
        }
    ]
}

```

Finally, run it using docker-compose
```shellsession
docker-compose -p sftp -f docker-compose.sftp.yaml up -d
```
