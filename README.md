# laravel-evolution

## Установка

Laravel
```
composer create-project laravel/laravel .
```
Evolution Core
```
composer require team64j/laravel-evolution
```
Evolution Api
```
composer require team64j/laravel-manager-api
```
JWT Token
```
php artisan jwt:secret
```

Скопировать в корень сайта файл /public/.htaccess

Создать в корне сайта файл index.php с содержимым
```php
<?php
require __DIR__ . '/public/index.php';
```

В корне сайта в .env файле настроить подключение к уже существующей БД и добавить настройку для вашего префикса таблиц

```dotenv
DB_PREFIX=
```

Перейти по адресу http://domain.com/manager/api
Если есть ответ в виде OpenApi схемы, значит всё работает.
