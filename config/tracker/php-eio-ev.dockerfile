FROM php

RUN apt-get update && \
    apt-get install htop

RUN pecl install eio && \
    pecl install ev && \
    docker-php-ext-enable eio ev

WORKDIR /app

CMD ["/bin/bash"]