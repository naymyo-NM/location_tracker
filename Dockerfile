FROM richarvey/nginx-php-fpm:3.1.6

COPY . /var/www/html

ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV COMPOSER_ALLOW_SUPERUSER=1

# Production defaults (override with env on Render)
ENV APP_ENV=production
ENV APP_DEBUG=0
ENV LOG_CHANNEL=stderr

RUN chmod +x /var/www/html/docker-entrypoint.sh

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["/start.sh"]
