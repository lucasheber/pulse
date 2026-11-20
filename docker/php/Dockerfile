FROM phpswoole/swoole:6.2-php8.5-alpine

WORKDIR /opt/www

RUN apk add --no-cache git unzip bash curl \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && docker-php-ext-install -j$(nproc) bcmath pcntl \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps \
    && { \
        echo "swoole.use_shortname = Off"; \
        echo "memory_limit = 512M"; \
        echo "date.timezone = UTC"; \
        echo "pcov.enabled = 0"; \
    } > /usr/local/etc/php/conf.d/hyperf.ini

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
