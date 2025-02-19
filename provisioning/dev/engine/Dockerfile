FROM php:7.4-fpm

RUN apt-get update

RUN apt-get install -y libpq-dev libssl-dev && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install pdo_pgsql sockets &&\
    docker-php-ext-install exif && docker-php-ext-enable exif && \
#    Install GD Extension
    apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash - &&  \
    apt-get install -y nodejs && \
    npm install --global yarn

RUN apt-get install -y git zip

RUN apt-get clean

COPY entrypoint.bash /entrypoint.bash
RUN chmod +x /entrypoint.bash
ENTRYPOINT ["/entrypoint.bash"]
CMD ["php-fpm"]
