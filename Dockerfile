FROM dunglas/frankenphp

ENV SERVER_NAME=:80

RUN install-php-extensions imap mysqli zip