FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC

# Packages
RUN apt-get update -y && apt-get upgrade -y && \
    apt-get install -y --no-install-recommends nginx-extras && \
    apt-get autoremove --purge -y && apt-get clean && rm -rf /var/lib/apt/lists/*

ADD etc/nginx/ /etc/nginx/
ADD etc/nginx/sites-enabled /etc/nginx/sites-enabled/
RUN rm /etc/nginx/sites-enabled/default

EXPOSE 80
WORKDIR /home/app

CMD ["nginx"]
