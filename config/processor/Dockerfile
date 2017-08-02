FROM php

RUN apt-get update && \
    apt-get install -y \
        git \
        htop \
        netcat \
        telnet \
        wget \
        zlib1g-dev

COPY config/shared/install_composer.sh /tmp/install_composer.sh
RUN chmod +x /tmp/install_composer.sh && /tmp/install_composer.sh

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
    pecl install ds && \
    pecl install eio && \
    pecl install ev && \
    pecl install libsodium && \
    docker-php-ext-enable ds eio ev sodium

RUN apt-get install --yes build-essential libtool autoconf automake && \
    cd /tmp && \
    git clone --recursive https://github.com/maxmind/libmaxminddb && \
    cd libmaxminddb && \
    ./bootstrap && \
    ./configure && \
    make && \
    make check && \
    make install && \
    ldconfig

RUN cd /tmp && \
    git clone --recursive https://github.com/maxmind/MaxMind-DB-Reader-php.git && \
    cd MaxMind-DB-Reader-php/ext && \
    phpize && \
    ./configure && \
    make && \
    make test && \
    make install && \
    docker-php-ext-enable maxminddb

ADD config/processor/geolite2_install.sh /tmp/geolite2_install.sh
RUN chmod +x /tmp/geolite2_install.sh && /tmp/geolite2_install.sh /app/var

RUN useradd -ms /bin/bash app
WORKDIR /app
USER app

CMD ["/bin/bash"]
