# NKCS CRM Api
> Simple, light and extensible api based on the symfony framework
>
> [![CI Status](https://github.com/Praesidiarius/nkcs-crm-api/workflows/CI/badge.svg)](https://github.com/Praesidiarius/nkcs-crm-api/actions)

## Install
- ``composer install``
- - copy `.env` to ``.env.local``
- Add values for ``APP_SECRET``, ``JWT_PASSPHRASE`` AND ``DATABASE_URL``
- ``php bin/console lexik:jwt:generate-keypair``
- ``php bin/console doctrine:database:create``
- ``php bin/console make:migration``
- ``php bin/console doctrine:migrations:migrate``

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

#### Testing the contact api

- Lets create a new Contact now. First, acquire a CSRF Token by sending a GET request to ``localhost:8000/api/contact/add``
- The API will tell you all the form Fields (for your client ui) and give you a CSRF Token to submit your form:
```JSON
{
    "form": {
        "firstName": [],
        "lastName": [],
        "isCompany": [],
        "emailPrivate": [],
        "emailBusiness": []
    },
    "token": "bfe01021e.qOID3VoOO0mShB8gtaJ5-JtejgTYfId....."
}
```

- Now you can create a new contact by sending a POST request to ``localhost:8000/api/contact/add`` with a JSON body like this:
```JSON
{
  "firstName": "Test",
  "lastName": "Contact",
  "isCompany": 0,
  "emailBusiness": "info@test.com",
  "_token": "YOUR_CSRF_TOKEN_FROM_GET_REQUEST"
} 
```
- You will get a response with the saved contact:
```JSON
{
  "item": {
    "id": 1,
    "firstName": "Test",
    "lastName": "Contact",
    "isCompany": false,
    "emailPrivate": null,
    "emailBusiness": "info@test.com",
    "createdBy": 1,
    "createdDate": "2023-04-28T13:19:22+00:00"
  }
}
```

- Now you can list your contacts with a GET request to ``localhost:8000/api/contact``
```JSON
{
  "headers": [
    {
      "text": "Vorname",
      "value": "firstName",
      "sortable": true,
      "type": "text"
    },
    {
      "text": "Nachname",
      "value": "lastName",
      "sortable": true,
      "type": "text"
    }
  ],
  "items": [
    {
      "id": 1,
      "firstName": "Test",
      "lastName": "Contact",
      "isCompany": false,
      "emailPrivate": null,
      "emailBusiness": "info@test.com",
      "createdBy": 1,
      "createdDate": "2023-04-28T13:19:22+00:00"
    }
  ],
  "total_items": 1,
  "pagination": {
    "pages": 1,
    "page_size": 35,
    "page": 1
  }
}
```
