# Image Search API Service 🖼️

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel)
![ElasticSearch](https://img.shields.io/badge/ElasticSearch-8.0-005571?logo=elasticsearch)
![Docker](https://img.shields.io/badge/Docker-✓-2496ED?logo=docker)
![Tests](https://img.shields.io/badge/Tests-100%25 passing-green)

Профессиональный микросервис для поиска и обработки изображений с API на основе подписок.

## 🚀 Возможности

- **🔍 Умный поиск** - полнотекстовый поиск по названиям, категориям и брендам через ElasticSearch
- **🖼️ Обработка изображений** - динамическое изменение размеров и форматов
- **🔐 API с подписками** - система ключей с лимитами запросов
- **⚡ Асинхронная обработка** - фоновая обработка через RabbitMQ
- **📊 Админ-панель** - полное управление контентом и подписками
- **🧪 Протестировано** - надежность и стабильность

## 🛠️ Технологический стек

| Компонент | Технология | Назначение |
|-----------|------------|------------|
| **Backend** | Laravel 12, PHP 8.3, Nginx | Основной фреймворк |
| **Поиск** | ElasticSearch | Быстрый полнотекстовый поиск |
| **Очереди** | RabbitMQ | Асинхронная обработка задач |
| **База данных** | PostgreSQL | Надежное хранение данных |
| **Кэширование** | Redis | Производительность |
| **Контейнеризация** | Docker, Docker Compose | Простое развертывание |

## 📦 Быстрый старт

```bash
# Клонирование и запуск
git clone https://github.com/vanyazaov/image-search-api-dev.git
cd image-search-api-dev
cd docker
docker compose up -d

# Создайте .env файл для настройки проекта
docker compose exec -T mpv_app bash -c "cd /var/www/html && cp .env.dev .env"

# Сгенерируйте ключ приложения
docker compose exec -T mpv_app bash -c "cd /var/www/html && php artisan key:generate --force"

# Установка зависимостей
docker compose exec mpv_app composer install
docker compose exec mpv_app php artisan migrate

# Заполните БД тестовыми пользователями (admin@example.com / password)
docker compose exec -T mpv_app bash -c "cd /var/www/html && php artisan db:seed --force"

# Запустите очередь
docker compose exec mpv_app php artisan queue:work

# Откройте сайт и авторизуйтесь как админ и разместите изображение
http://localhost/

# При необходимости исправьте права на папки
chmod +x scripts/fix-permissions.sh
./scripts/fix-permissions.sh

# Если поиск не работает, то скорее всего не создался индекс. Исправьте права на папки и запустите индексацию
docker compose exec mpv_app php artisan search:setup


# Запуск тестов

# создайте тестовое окружение (настройки, база данных)
chmod +x scripts/test-setup.sh
./scripts/test-setup.sh
docker compose exec mpv_app php artisan test
```

## 🔌 API Документация

### Поиск изображений
```http
GET /api/v1/search?q=shoes&category=footwear&brand=nike
Headers: X-API-Key: your_api_key_here
```

### Получение изображения
```http
GET /api/v1/images/{id}?w=800&h=600&q=85
Headers: X-API-Key: your_api_key_here
```

## 🎯 Ключевые архитектурные решения

- **Микросервисная архитектура** - каждый компонент независим
- **Event-Driven Design** - асинхронная обработка через очереди
- **RESTful API** - чистые и предсказуемые endpoints
- **Horizontal Scaling** - готовность к масштабированию

graph TB
    A[Клиент] --> B[NGINX]
    B --> C[Laravel App]
    C --> D[PostgreSQL]
    C --> E[Redis]
    C --> F[ElasticSearch]
    C --> G[RabbitMQ]
    G --> H[Queue Workers]
    H --> I[Image Processing]
    H --> J[Search Indexing]

## 👨‍💻 Автор

[Ivan Zayashnikov] - [GitHub](https://github.com/vanyazaov)
