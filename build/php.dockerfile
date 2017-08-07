ARG VERSION

FROM php:${VERSION}

ENV BASE_PACKAGES git htop iotop netcat wget
ENV PHP_DEPENDENCIES zlib1g-dev

RUN apt-get update && apt-get install -y ${BASE_PACKAGES} ${PHP_DEPENDENCIES}

ENV CORE_EXTENSIONS bcmath mbstring opcache pcntl pdo_mysql sysvsem sysvshm zip

RUN docker-php-ext-install ${CORE_EXTENSIONS}

ENV PECL_EXTENSIONS ds eio ev

RUN pecl install ${PECL_EXTENSIONS} && docker-php-ext-enable ${PECL_EXTENSIONS}

ENV LIBSODIUM_VERSION 1.0.13
ENV LIBSODIUM libsodium-${LIBSODIUM_VERSION}

# cannot instal with apt-get due to old version
RUN cd /tmp && \
    wget https://download.libsodium.org/libsodium/releases/${LIBSODIUM}.tar.gz && \
    tar zxf ${LIBSODIUM}.tar.gz && \
    cd ${LIBSODIUM} && \
    ./configure && \
    make && make check && \
    make install && \
    cd /tmp && rm -rf ${LIBSODIUM} && \
    pecl install libsodium && \
    docker-php-ext-enable sodium

RUN wget -qO- https://getcomposer.org/installer | php -- --install-dir /usr/local/bin --filename=composer

CMD ["php", "-a"]
