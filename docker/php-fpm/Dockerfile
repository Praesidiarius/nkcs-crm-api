FROM php:8.3-fpm-alpine3.19

RUN apk add --no-cache bash curl

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash

RUN apk --update add \
    bash \
    curl \
    unzip \
    gnupg \
    zlib \
    zlib-dev \
    nodejs \
    npm \
    build-base \
    git \
    util-linux \
    linux-headers \
    icu-dev \
    libpng \
    libpng-dev \
    libjpeg \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    libstdc++ \
    libx11 \
    libxrender \
    libxext \
    libzip \
    libzip-dev \
    ca-certificates \
    fontconfig \
    freetype \
    ttf-droid \
    ttf-freefont \
    symfony-cli \
    imagemagick-dev \
    autoconf

RUN docker-php-ext-configure gd --with-jpeg

RUN docker-php-ext-install pdo mysqli pdo_mysql zip intl opcache gd

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && pecl install apcu \
    && docker-php-ext-enable apcu

# uncomment below part to actually start using & enabling xdebug
RUN pecl install xdebug # && docker-php-ext-enable xdebug

RUN pecl install pcov && docker-php-ext-enable pcov

RUN pecl clear-cache \
    && apk del .build-dependencies

# Currently, and for some time the PECL version of the extension is broken on PHP 8.3
# https://orkhan.dev/2024/02/07/using-imagick-with-php-83-on-docker/
RUN apk add git --update --no-cache && \
    git clone https://github.com/Imagick/imagick.git --depth 1 /tmp/imagick && \
    cd /tmp/imagick && \
    git fetch origin master && \
    git switch master && \
    cd /tmp/imagick && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    docker-php-ext-enable imagick

COPY ./xdebug.ini "$PHP_INI_DIR/conf.d"

RUN curl -sL https://getcomposer.org/installer | php -- --install-dir /usr/bin --filename composer

RUN npm install -g corepack

RUN corepack enable

RUN corepack prepare yarn@stable --activate

RUN yarn config set --home enableTelemetry 0

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN sed -i 's/memory_limit = 128M/memory_limit = -1/g' ${PHP_INI_DIR}/php.ini

# uncomment this if you need to authenticate for some github or any other git repos
# COPY ./composer/auth.json /root/.composer/auth.json

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]
