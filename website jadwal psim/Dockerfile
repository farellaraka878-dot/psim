FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN install-php-extensions pdo_mysql mbstring \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY Caddyfile /etc/caddy/Caddyfile
COPY htdocs/ ./htdocs/

ENV PORT=80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
