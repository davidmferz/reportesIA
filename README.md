# ReportesIA

Sistema de gestión con autenticación, roles y auditoría desarrollado en Laravel 12.

## Características

- Autenticación completa con Laravel Breeze (registro, login, recuperación de contraseña)
- Sistema de roles (Administrador/Usuario)
- CRUD completo de usuarios
- Sistema de auditoría automática para tracking de cambios
- Interfaz moderna con Tailwind CSS y modo oscuro
- Diseño responsive

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js y NPM
- SQLite (o base de datos de tu preferencia)

## Instalación

1. Instalar dependencias de PHP:
```bash
composer install
```

2. Instalar dependencias de Node.js:
```bash
npm install
```

3. Configurar archivo de entorno:
```bash
cp .env.example .env
php artisan key:generate
```

4. Crear base de datos SQLite:
```bash
touch database/database.sqlite
```

5. Ejecutar migraciones y seeders:
```bash
php artisan migrate:fresh --seed
```

6. Compilar assets:
```bash
npm run build
```

7. Iniciar servidor de desarrollo:
```bash
php artisan serve
```

## Credenciales de Acceso

**Usuario Administrador:**
- Email: `admin@reportesia.com`
- Password: `password`

## Estructura del Proyecto

- **Autenticación:** Laravel Breeze con Blade + Alpine.js
- **Sistema de Roles:** Middleware personalizado `admin`
- **Auditoría:** Trait reutilizable `LogsActivity` para tracking automático
- **CRUD Usuarios:** Módulo completo en `/admin/users`

## Documentación

Para información detallada sobre la arquitectura, patrones implementados y guías de desarrollo, consulta el archivo [AGENTE.MD](AGENTE.MD).

## Stack Tecnológico

- Laravel 12.x
- PHP 8.2+
- Tailwind CSS 3.x
- Alpine.js
- SQLite (desarrollo)

## Próximos Pasos

1. Cambiar las credenciales por defecto en producción
2. Configurar base de datos MySQL/PostgreSQL para producción
3. Implementar más módulos según necesidades del negocio
4. Agregar testing automatizado

## Licencia

Este proyecto es privado y propietario.
