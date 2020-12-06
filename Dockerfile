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
    unzip \
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

RUN a2enmod rewrite && a2dissite 000-default

ENV APACHE_CONFDIR /etc/apache2
RUN { \
        echo '<VirtualHost *:80>'; \
        echo 'DocumentRoot /Votix/public'; \
        echo 'DirectoryIndex index.php index.html'; \
        echo '<Directory /Votix/public>'; \
	echo '   Require all granted'; \
        echo '   AllowOverride All'; \
        echo '</Directory>'; \
        echo '</VirtualHost>'; \
    } | tee "$APACHE_CONFDIR/sites-available/votix.conf" \
    && a2ensite votix


RUN ln -sf /proc/$$/fd/1 /var/log/apache2/access.log && ln -sf /proc/$$/fd/2 /var/log/apache2/error.log

WORKDIR /

# Composer install
RUN php -r "copy('https://getcomposer.org/download/1.10.19/composer.phar', 'composer.phar');" && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

# RUN git clone https://github.com/ESIEESPACE/Votix -b docker

COPY . /Votix

WORKDIR /Votix

RUN composer install

RUN php bin/console assets:install public --symlink

RUN node --version
RUN yarn install
RUN yarn run build

RUN chmod -R 777 /Votix/var

RUN cp config/services.docker.yaml config/services.yaml

RUN mkdir /db

ENV DATABASE_URL sqlite:///%kernel.project_dir%/var/votix.sqlite
ENV MAILER_URL null://localhost
ENV APP_ENV prod
ENV APP_DEBUG 1
ENV LOCALE en
ENV SHELL_VERBOSITY 3

# RUN php ./bin/console doctrine:fixtures:load --no-interaction

COPY docker-startup.sh .

VOLUME [ "/data" ]
EXPOSE 80
CMD ./docker-startup.sh
