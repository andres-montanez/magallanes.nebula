FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC

# Packages
RUN apt-get update -y && apt-get upgrade -y && \
    apt-get install -y --no-install-recommends software-properties-common && add-apt-repository -y ppa:ondrej/php && apt-get update && \
    apt-get install -y --no-install-recommends supervisor openssh-client ca-certificates curl vim git unzip \
        php8.0-cli php8.0-xml php8.0-mysql php8.0-curl php8.0-ssh2 php8.0-mbstring php8.0-zip php8.0-intl && \
    apt-get autoremove --purge -y && apt-get clean && rm -rf /var/lib/apt/lists/*

ADD etc/supervisor/ /etc/supervisor/
ADD etc/php/8.0/cli/conf.d/ /etc/php/8.0/cli/conf.d/

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
RUN composer self-update --2

WORKDIR /home/app

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
