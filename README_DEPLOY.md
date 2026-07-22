# Network Log Monitor 3.1 — Railway

## Обязательные переменные Railway

- `ADMIN_LOGIN` — логин панели.
- `ADMIN_PASSWORD` — новый сложный пароль, не оставляйте `admin123`.
- `APP_URL` — необязательно. Если не задан, приложение использует `RAILWAY_PUBLIC_DOMAIN` автоматически.
- `DATA_DIR=/data` — используйте вместе с Railway Volume.
- `LOG_MAX_ENTRIES=5000` — максимальный размер JSON-журнала.

## Постоянное хранение

Приложение хранит журнал и GEO-кэш в `DATA_DIR`. Для Railway подключите Volume с mount path `/data`, иначе данные файлового хранилища могут исчезнуть после нового deployment.

## Публичные адреса

Все внутренние ссылки и AJAX-запросы строятся через `APP_URL`. На Railway адрес определяется автоматически из `RAILWAY_PUBLIC_DOMAIN`. Текущий адрес можно явно задать:

```env
APP_URL=https://testalimov-production.up.railway.app
```

## Конфиденциальность

Публичная страница сохраняет обычные технические данные HTTP-запроса. GPS-координаты отправляются только после отдельного нажатия пользователя и разрешения браузера.

## Health check

Для проверки готовности сервиса задайте Railway Healthcheck Path: `/health.php`.
