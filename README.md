# NKCS CRM Api
[![CI Status](https://github.com/Praesidiarius/nkcs-crm-api/workflows/CI/badge.svg)](https://github.com/Praesidiarius/nkcs-crm-api/actions)
> Simple, light and extensible api based on the symfony framework

## Install
- ``composer install``
- - copy `.env` to ``.env.local``
- Add values for ``APP_SECRET``, ``JWT_PASSPHRASE`` AND ``DATABASE_URL``
- ``php bin/console lexik:jwt:generate-keypair``

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