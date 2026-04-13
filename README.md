### Hexlet tests and linter status:
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)
[![Actions Status](https://github.com/Kromian1/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Kromian1/php-project-9/actions)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=bugs)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=Kromian1_php-project-9&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=Kromian1_php-project-9)


## Демо:
[Анализатор страниц на Render.com](https://php-project-9-d7cw.onrender.com)

## Описание

**Анализатор страниц** — это веб-приложение для SEO-анализа сайтов. Оно проверяет:

- Статус-код ответа
- Наличие и содержание заголовка `<h1>`
- Наличие и содержание заголовка `<title>`
- Наличие и содержание мета-тега `<meta name="description">`

Приложение сохраняет историю проверок для каждого добавленного сайта и позволяет отслеживать изменения SEO-параметров во времени.

## Функциональность

- ✅ Добавление сайтов по URL
- ✅ Автоматическая нормализация URL (удаление trailing slash, приведение к нижнему регистру)
- ✅ Защита от дубликатов
- ✅ Валидация URL (формат, длина, обязательность)
- ✅ Проверка сайтов с сохранением результатов
- ✅ Парсинг HTML-страниц (h1, title, description)
- ✅ Отображение списка всех добавленных сайтов с последним статусом проверки
- ✅ Детальная страница каждого сайта с историей проверок
- ✅ Flash-сообщения об успехе/ошибках
- ✅ Адаптивный дизайн на Bootstrap

## Технологии

| Компонент | Технология |
|-----------|------------|
| **Backend** | PHP 8.4 |
| **Фреймворк** | Slim 4 |
| **Шаблонизация** | Slim PHP-View |
| **База данных** | PostgreSQL |
| **ORM/DBAL** | PDO |
| **HTTP-клиент** | Guzzle |
| **Парсинг HTML** | Symfony DomCrawler |
| **Валидация** | Valitron |
| **Frontend** | Bootstrap 5 |
| **Статический анализ** | PHP_CodeSniffer |
| **CI/CD** | GitHub Actions |

## Структура

    src/
    ├── Analyzer/
    │   ├── HttpClient.php      # HTTP-запросы через Guzzle
    │   ├── HtmlParser.php      # Парсинг HTML (DomCrawler)
    │   ├── UrlValidator.php    # Валидация и нормализация URL
    │   ├── TimeNormalizer.php  # Форматирование дат
    │   └── CheckNormalizer.php # Форматирование данных проверок
    ├── Db/
    │   ├── Connection.php      # Подключение к PostgreSQL
    │   └── UrlRepository.php   # Работа с БД (DAO)
    templates/
    ├── layout.phtml            # Базовый шаблон (Bootstrap, меню)
    ├── index.phtml             # Главная страница (форма добавления)
    └── urls/
        ├── urls.phtml          # Список всех сайтов
        └── url.phtml           # Детальная страница сайта

## Требования

- PHP 8.4 или выше
- PostgreSQL 16 или выше
- Composer
- Make

## Установка и запуск

### 1. Клонирование репозитория

git clone https://github.com/Kromian1/php-project-9.git
cd php-project-9

### 2. Установка зависимостей

make install

### 3. Настройка базы данных

Создайте базу данных PostgreSQL:
createdb page_analyzer_dev

Экспортируйте переменную окружения:
export DATABASE_URL=postgresql://localhost:5432/page_analyzer_dev

Выполните миграцию:
psql -a -d $DATABASE_URL -f database.sql

### 4. Запуск приложения

make start
Приложение будет доступно по адресу: http://localhost:8000

### 5. Запуск тестов

make test

### 6. Линтинг кода

make lint

## API Эндпоинты
| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/` | Главная страница с формой добавления URL |
| POST | `/urls` | Добавление нового URL |
| GET | `/urls` | Список всех добавленных URL |
| GET | `/urls/{id}` | Детальная информация о сайте и его проверках |
| POST | `/urls/{id}/checks` | Запуск проверки сайта |

## Примеры запросов

Добавление сайта

curl -X POST http://localhost:8000/urls \
  -d "url=https://example.com"
  
Проверка сайта

curl -X POST http://localhost:8000/urls/1/checks

## Деплой
Приложение задеплоено на Render.com:

Веб-сервис: https://php-project-9-d7cw.onrender.com

База данных: PostgreSQL (создаётся через Render)

Для собственного деплоя:

Создайте PostgreSQL на Render

Добавьте переменную окружения DATABASE_URL в настройках веб-сервиса

Выполните миграцию: psql -d $DATABASE_URL -f database.sql
