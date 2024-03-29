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
```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Start server
```
symfony server:start
```

## Create a new api client
```
php bin/console synchronizer:client:create "client_name"
```
This command will give to you an api key that should be used on each call as a query param ('ak_' preffix should be added to the api key)
```
api/merchants/{siretId}?api_key=ak_9efcd259266235814829bfb9f2132acac76f0b27
```

## API docs

http://localhost:8000/api
