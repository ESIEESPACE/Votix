FROM debian:stretch

RUN apt update && apt upgrade -y

RUN apt install -y \
    apt-transport-https \
    lsb-release \
    ca-certificates \
    wget \
    curl \
    apache2 \
    gnupg2 \
    git \
    --no-install-recommends

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN curl -sL https://deb.nodesource.com/setup_15.x | bash -

RUN apt update && apt install -y yarn nodejs


# Add ondrej sources for old php packages
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# PHP & Extensions
RUN DEBIAN_FRONTEND=noninteractive apt update && apt upgrade -y && apt install -y \
    php7.4 \
    php7.4-bcmath \
    php7.4-curl \
    php7.4-mbstring \
    php7.4-gd \
    php7.4-mysql \
    php7.4-sqlite3 \
    php7.4-soap \
    php7.4-xml \
    php7.4-zip \
    php7.4-xdebug

RUN a2enmod rewrite

STOPSIGNAL WINCH
WORKDIR /

# Composer install
RUN php -r "copy('https://getcomposer.org/download/1.10.19/composer.phar', 'composer.phar');" && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

RUN git clone https://github.com/ClubNix/Votix

WORKDIR /Votix

RUN composer install

#RUN composer recipes:install --force -v
#RUN composer req orm-pack --unpack
RUN php bin/console assets:install public --symlink

RUN node --version
RUN yarn install
RUN yarn run build

RUN ln -s /Votix/public /var/www && mv /var/www/public /var/www/html
RUN chmod -R 777 /Votix/var


# Be careful
RUN php ./bin/console doctrine:database:drop --force
RUN php ./bin/console doctrine:database:create
RUN php ./bin/console doctrine:schema:update --force
RUN php ./bin/console doctrine:fixtures:load --no-interaction


EXPOSE 80
CMD apachectl -D FOREGROUND
