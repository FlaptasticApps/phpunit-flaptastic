FROM php:5.6-cli

# Work out of this directory
WORKDIR /usr/flaptastic

# Install composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    zip \
    unzip \
    make \
    && apt-get clean \
    && rm -r /var/lib/apt/lists/*

# https://www.sentinelstand.com/article/composer-install-in-dockerfile-without-breaking-cache
ENV COMPOSER_ALLOW_SUPERUSER 1
COPY composer.json .
RUN composer install --no-scripts --no-autoloader

# Link all code into container
COPY . .

# Build the composer autoloader after source code copied
RUN composer dump-autoload --optimize

# Run tests
CMD [ "./vendor/bin/phpunit" ]
