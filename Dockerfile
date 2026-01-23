FROM php:8.4-fpm

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y git postgresql-client unzip

RUN chmod +x /usr/local/bin/install-php-extensions && sync && install-php-extensions gd zip soap pdo_pgsql pcntl intl

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get install -y nodejs

ENV NODE_VERSION=20.9.0
RUN apt install -y curl
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
ENV NVM_DIR=/root/.nvm
RUN . "$NVM_DIR/nvm.sh" && nvm install ${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm use v${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm alias default v${NODE_VERSION}
ENV PATH="/root/.nvm/versions/node/v${NODE_VERSION}/bin/:${PATH}"
RUN node --version
RUN npm --version

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/default
# Fix Laravel permissions automatically
# RUN mkdir -p /var/www/default/storage /var/www/default/bootstrap/cache \
#     && chown -R www-data:www-data /var/www/default/storage /var/www/default/bootstrap/cache \
#     && chmod -R 775 /var/www/default/storage /var/www/default/bootstrap/cache



CMD "/usr/local/sbin/php-fpm"
