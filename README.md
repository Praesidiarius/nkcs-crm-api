# NKCS CRM Api
[![CI Status](https://github.com/Praesidiarius/nkcs-crm-api/workflows/CI/badge.svg)](https://github.com/Praesidiarius/nkcs-crm-api/actions)
[![codecov](https://codecov.io/gh/Praesidiarius/nkcs-crm-api/branch/main/graph/badge.svg?token=Z01K94CXNN)](https://codecov.io/gh/Praesidiarius/nkcs-crm-api)
> Simple, light and extensible api based on the symfony framework

## Install
- ``composer install``
- - copy `.env.example` to ``.env.local``
- copy `docker/database/.env.example` to ``docker/database/.env``
- Add values for ``APP_SECRET`` AND ``DATABASE_URL``
- ``php bin/console lexik:jwt:generate-keypair``
- ``mkcert -cert-file docker/nginx/crm-api.local.crt -key-file docker/nginx/crm-api.local.key crm-api.local``
- ``mkcert -install``
- ``docker compose build``
- ``docker compose up``
- ``add 127.0.0.1 crm-api.local to your hosts file``

The CRM API is now running locally in docker at crm-api.local

### Add a first user to test api
- ``php bin/console security:hash-password`` - make a hash for YOURPASSWORD
- Import your new user to your database:
```SQL
INSERT INTO `user` (`id`, `username`, `roles`, `password`) VALUES (NULL, 'USERNAME', '[]', 'YOURPASSWORDHASH'); 
```

### Testing the api
#### Acquire an JWT Token for your user
- ``symfony server:start``
- Use Postman to send a POST request to ``localhost:8000/api/login_check`` with this JSON Body
```JSON
  {
  "username": "USERNAME",
  "password": "YOURPASSWORD"
  }
```
- You will get a response like this:
```JSON
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1N...."
}
```
- You can now use this Token as Bearer Token in the AUTHORIZATION Header for all API Requests
