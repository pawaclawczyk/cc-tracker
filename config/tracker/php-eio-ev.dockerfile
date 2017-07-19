FROM php

RUN apt-get update && \
    apt-get install -y \
        git \
        htop \
        wget \
        zlib1g-dev

COPY config/tracker/install_composer.sh /tmp/install_composer.sh
RUN chmod +x /tmp/install_composer.sh && \
     /tmp/install_composer.sh

ENV libsodium libsodium-1.0.13

RUN cd /tmp && \
    wget https://download.libsodium.org/libsodium/releases/${libsodium}.tar.gz && \
    tar zxf ${libsodium}.tar.gz && \
    cd ${libsodium} && \
    ./configure && \
    make && make check && \
    make install && \
    cd /tmp && rm -rf ${libsodium}


RUN docker-php-ext-install opcache pcntl pdo_mysql sysvsem sysvshm zip && \
    pecl install eio && \
    pecl install ev && \
    pecl install libsodium && \
    docker-php-ext-enable eio ev sodium

# uopz 5.0.1 is not compatible with PHP 7.1 yet see: https://github.com/krakjoe/uopz/issues/57
#RUN pecl install uopz xdebug && \
#    docker-php-ext-enable uopz xdebug

COPY config/tracker/php.ini /usr/local/etc/php/php.ini

RUN useradd -ms /bin/bash app
WORKDIR /app
USER app

CMD ["/bin/bash"]
