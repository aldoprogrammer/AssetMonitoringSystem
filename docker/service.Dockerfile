FROM php:8.3-cli-bookworm AS builder

ARG SERVICE_DIR

WORKDIR /workspace

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl libpq-dev librabbitmq-dev \
    && docker-php-ext-install pdo_pgsql pcntl sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/scaffold-service.sh /usr/local/bin/scaffold-service.sh
COPY ${SERVICE_DIR} /tmp/service

RUN chmod +x /usr/local/bin/scaffold-service.sh \
    && /usr/local/bin/scaffold-service.sh /tmp/service /var/www/app

FROM php:8.3-cli-alpine AS runtime

RUN apk add --no-cache bash curl libpq rabbitmq-c \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers postgresql-dev rabbitmq-c-dev \
    && docker-php-ext-install pdo_pgsql pcntl sockets \
    && apk del .build-deps

WORKDIR /var/www/app

COPY --from=builder /var/www/app /var/www/app
COPY docker/service-entrypoint.sh /usr/local/bin/service-entrypoint.sh

RUN chmod +x /usr/local/bin/service-entrypoint.sh \
    && chown -R www-data:www-data /var/www/app

EXPOSE 8000

ENTRYPOINT ["service-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
