FROM phpswoole/swoole:6.2-php8.5-alpine

WORKDIR /opt/www

RUN apk add --no-cache git unzip bash curl \
    && docker-php-ext-install -j$(nproc) bcmath pcntl \
    && { \
        echo "swoole.use_shortname = Off"; \
        echo "memory_limit = 512M"; \
        echo "date.timezone = UTC"; \
    } > /usr/local/etc/php/conf.d/hyperf.ini

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
