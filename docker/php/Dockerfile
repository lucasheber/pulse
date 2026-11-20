FROM hyperf/hyperf:8.3-alpine-v3.20-swoole

WORKDIR /opt/www

RUN apk add --no-cache git unzip bash curl

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
