# Tienda Moroni

## Desarrollo local

```bash
php -S localhost:8000 -t public_html
```

## Mantenimiento de base de datos

Ejecutar periódicamente (cron job recomendado) para limpiar tokens expirados:

```sql
DELETE FROM password_resets WHERE expires_at < NOW();
DELETE FROM password_reset_attempts WHERE created_at < NOW() - INTERVAL 1 DAY;
```
