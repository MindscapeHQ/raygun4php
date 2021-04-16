## Setup instructions

1. Install composer dependencies with `composer install`
2. Copy `config-sample.php` and rename to `config.php`
3. Insert your API key from Raygun into the `API_KEY` field
4. Submit the form with empty/zero values to trigger errors

### Docker

This sample app is set up to run at http://localhost:8888 by default. You can change the port in the `docker-compose.yml` file.

1. Ensure dependencies are installed (skip this step if this has been done already):

```
composer install
```

2. Using docker-compose:

```
docker-compose up
```

3. Or more manually with the docker CLI:

```
docker build -t async-example .
docker run -p 8888:80 -d --name async-example async-example-web
```
