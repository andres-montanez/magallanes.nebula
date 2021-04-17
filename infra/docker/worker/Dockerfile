FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC

# Packages
RUN apt-get update -y && apt-get upgrade -y && \
    apt-get install -y --no-install-recommends supervisor openssh-client ca-certificates curl vim git unzip php-cli php-mysql php-xml php-soap php-curl php-ssh2 php-mbstring php-memcached php-zip php-intl php-sqlite3 && \
    apt-get autoremove --purge -y && apt-get clean && rm -rf /var/lib/apt/lists/*

ADD etc/supervisor/ /etc/supervisor/
ADD etc/php/7.4/cli/conf.d/ /etc/php/7.4/cli/conf.d/

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
RUN composer self-update --2

WORKDIR /home/app

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]