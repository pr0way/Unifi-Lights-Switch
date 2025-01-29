FROM php:8.2-cli

WORKDIR /app/

RUN apt-get update && apt-get install -y \
    cron \
    zip \
    unzip \
    git \
    && apt-get clean

COPY ./app /app/
COPY ./crontab /etc/cron.d/app-cron
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN chmod 0644 /etc/cron.d/app-cron && \
    crontab /etc/cron.d/app-cron && \
    touch /var/log/cron.log && \
    ln -s /usr/local/bin/php /usr/bin/php && \
    composer install --no-dev --optimize-autoloader && \
    printenv | grep -v "no_proxy" >> /etc/environment

CMD ["sh", "-c", "printenv > /etc/environment; cron && tail -f /var/log/cron.log"]
