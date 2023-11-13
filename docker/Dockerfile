FROM ubuntu:latest

# install dependecnies
RUN apt-get -y update && apt-get -y upgrade && \
    DEBIAN_FRONTEND=noninteractive TZ="Europe/Prague"  apt-get -y install tzdata curl lsb-release gnupg2 ca-certificates apt-transport-https software-properties-common && \
    LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php && \
    apt-get -y update && apt-get -y install php8.2 zip php-xml php-mbstring php-intl php-pgsql

# install composer
RUN curl -fsLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version=2.6.5

# install symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

EXPOSE 8080

WORKDIR /app

CMD ["symfony", "server:start", "--port=8080"]