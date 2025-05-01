### Hexlet tests and linter status:
[![Actions Status](https://github.com/tsoyvit/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/tsoyvit/php-project-9/actions)
[![Lint](https://github.com/tsoyvit/php-project-9/actions/workflows/lint.yml/badge.svg)](https://github.com/tsoyvit/php-project-9/actions/workflows/lint.yml)
[![Maintainability](https://api.codeclimate.com/v1/badges/78e251a4b7fda17ee611/maintainability)](https://codeclimate.com/github/tsoyvit/php-project-9/maintainability)


## Описание

Приложение для анализа состояния сайтов по URL.
Позволяет добавлять сайты, проверять их на доступность и извлекать данные (status code, title, h1, description).

## Деплой

Приложение доступно по адресу:
https://php-project-9-4syp.onrender.com

## Используемые технологии:
1. PHP 8.2 — язык программирования
2. Slim 4 — микрофреймворк для построения REST-приложений
3. PSR-7 (slim/psr7) — реализация HTTP-сообщений
4. PHP-DI — контейнер внедрения зависимостей
5. Slim Flash — flash-сообщения между редиректами
6. Slim PHP-View — простой рендерер для шаблонов на PHP
7. Guzzle — HTTP-клиент для отправки запросов
8. DiDOM — парсер HTML на основе DOM
9. Carbon — работа с датами и временем
10. PHP Dotenv — загрузка переменных окружения из .env
11. Monolog — логирование
12. PDO — доступ к базе данных PostgreSQL


## Установка

1. Склонируйте репозиторий и установите зависимости:

```bash
git clone https://github.com/tsoyvit/php-project-9.git
cd php-project-9
composer install
```

2. Создайте файл .env в корне проекта и добавьте туда строку подключения к базе данных PostgreSQL:

```bash
DATABASE_URL=postgres://username:password@localhost:5432/your_database
```

3. Создайте базу данных и выполните SQL-скрипт database.sql:

```bash
createdb your_database
psql your_database < database.sql
```
4. Запустите встроенный сервер:

```bash
make start
```

## Использование

1. Перейдите в браузере по адресу http://localhost:8002
2. Введите любой URL сайта 
3. После валидации сайт можно отправить на анализ

