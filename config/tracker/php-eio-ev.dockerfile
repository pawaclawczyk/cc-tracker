FROM php

RUN apt-get update && \
    apt-get install htop

RUN docker-php-ext-install pdo_mysql && \
    pecl install eio && \
    pecl install ev && \
    docker-php-ext-enable eio ev pdo_mysql

WORKDIR /app

CMD ["/bin/bash"]