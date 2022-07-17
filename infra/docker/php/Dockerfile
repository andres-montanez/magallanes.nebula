FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC

# Packages
RUN apt-get update -y && apt-get upgrade -y && \
    apt-get install -y --no-install-recommends software-properties-common && add-apt-repository -y ppa:ondrej/php && apt-get update && \
    apt-get install -y --no-install-recommends ca-certificates curl vim git unzip ssh-client \
        php8.0-cli php8.0-fpm php8.0-xml php8.0-mysql php8.0-curl php8.0-ssh2 php8.0-mbstring php8.0-zip php8.0-intl && \
    apt-get autoremove --purge -y && apt-get clean && rm -rf /var/lib/apt/lists/*

ADD etc/php/8.0/cli/conf.d/ /etc/php/8.0/cli/conf.d/
ADD etc/php/8.0/fpm/conf.d/ /etc/php/8.0/fpm/conf.d/
ADD etc/php/8.0/fpm/pool.d/www.conf /etc/php/8.0/fpm/pool.d/

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
RUN composer self-update --2

# Code Sniffer
RUN curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
RUN mv phpcs.phar /usr/bin/phpcs
RUN chmod +x /usr/bin/phpcs

# Code Sniffer - Fixer
RUN curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar
RUN mv phpcbf.phar /usr/bin/phpcbf

RUN mkdir /run/php
WORKDIR /home/app

CMD ["php-fpm8.0", "-F"]
