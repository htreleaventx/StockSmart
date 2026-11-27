FROM php:8.2-cli
RUN docker-php-ext-install pdo_mysql
WORKDIR /app
COPY . .
CMD ["sh", "-c", "printf '%s\n' \"${STOCKSMART_MANAGER_PASSWORD:-Admin1234}\" | php server/create-manager.php manager && php -S 0.0.0.0:${PORT:-8000} -t frontend"]
