FROM php:8.5-cli-trixie

ARG USER="assetic"

RUN \
    # Install OS dependencies
    apt-get update && \
    apt-get -y install \
        jpegoptim \
        libjpeg-progs \
        optipng && \
    # Install PHP extensions
    curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        @composer \
        gd \
        xdebug && \
    # Install Node 26 and Node dependencies
    curl -fsSL https://deb.nodesource.com/setup_26.x | bash - && \
    apt-get install -y nodejs && \
    # Install Tailwind CSS CLI (both v3 and v4)
    curl -fsSL https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-x64 -o /usr/local/bin/tailwindcss && chmod +x /usr/local/bin/tailwindcss && \
    curl -fsSL https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.19/tailwindcss-linux-x64 -o /usr/local/bin/tailwindcss3 && chmod +x /usr/local/bin/tailwindcss3 && \
    # Cleanup
    apt-get autoremove -y && \
    apt-get clean -y && \
    rm -rf /var/lib/apt/lists/*
