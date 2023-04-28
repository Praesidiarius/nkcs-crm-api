# NKCS CRM Api

## Install
- ``composer install``
- ``php bin/console lexik:jwt:generate-keypair``
- copy `.env` to ``.env.local``
- Add values for ``APP_SECRET``, ``JWT_PASSPHRASE`` AND ``DATABASE_URL``
- ``php bin/console doctrine:database:create``
- ````