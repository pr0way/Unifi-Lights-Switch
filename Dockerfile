FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    cron \
    zip \
    unzip \
    git \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/

COPY ./app /app/
COPY ./crontab /etc/cron.d/app-cron

RUN composer install --no-dev --optimize-autoloader
RUN chmod 0644 /etc/cron.d/app-cron && \
    crontab /etc/cron.d/app-cron
RUN touch /var/log/cron.log && ln -s /usr/local/bin/php /usr/bin/php
RUN printenv | grep -v "no_proxy" >> /etc/environment

CMD ["sh", "-c", "printenv > /etc/environment; cron && tail -f /var/log/cron.log"]
