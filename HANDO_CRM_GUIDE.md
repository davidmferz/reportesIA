# Hando CRM - Guía de Implementación

Sistema de dashboard CRM diseñado con el estilo "Hando" utilizando Laravel 12, Tailwind CSS y Alpine.js.

## Características Implementadas

### 1. Sistema de Diseño Hando

#### Colores Personalizados
Los colores están definidos en `tailwind.config.js`:

**Modo Claro:**
- Fondo principal: `#F4F7FA` (`hando-bg-light`)
- Tarjetas: `#FFFFFF` (`hando-card-light`)
- Texto: `#1E293B` (`hando-text-light`)

**Modo Oscuro:**
- Fondo principal: `#0F172A` (`hando-bg-dark`)
- Tarjetas: `#1E293B` (`hando-card-dark`)
- Texto: `#F8FAFC` (`hando-text-dark`)

**Acciones:**
- Primario Azul: `#3B82F6` (`hando-primary`)
- Peligro/Cancelar: `#F43F5E` (`hando-danger`)

**Bordes:**
- Modo Claro: `#D1D5DB` (`hando-border-light`)
- Modo Oscuro: `#334155` (`hando-border-dark`)

#### Border Radius
- Redondeado estándar: `8px` (usar clase `rounded-hando`)

### 2. Componentes Blade Creados

#### Layout y Estructura

**`layouts/crm.blade.php`**
- Layout principal del CRM con sidebar fijo y navbar superior
- Integración de Alpine.js para dark mode
- Manejo de localStorage para persistencia del tema

#### Navegación

**`components/crm/sidebar.blade.php`**
- Sidebar fijo de 64 (256px) de ancho
- Logo de la aplicación
- Navegación con 4 secciones:
  - **MENU**: Dashboard, Analytics
  - **PAGES**: Profile (con submenú expandible)
  - **APPS**: Email (con badge), Chat, Calendar
  - **GENERAL**: Settings
- Indicador visual azul para elemento activo
- Submenús colapsables con Alpine.js

**`components/crm/navbar.blade.php`**
- Barra superior fija
- Buscador con ícono de lupa
- Toggle de Dark/Light mode
- Notificaciones con badge rojo y dropdown
- Menú de perfil con avatar y dropdown

#### UI Components

**`components/hando-input.blade.php`**
- Input mejorado con soporte para íconos
- Border radius de 8px
- Estilos adaptativos para dark mode
- Focus ring con color primario

**`components/hando-button.blade.php`**
- Botón con 3 variantes: `primary`, `secondary`, `danger`
- 3 tamaños: `sm`, `md`, `lg`
- Transiciones suaves
- Sombras sutiles

**`components/hando-label.blade.php`**
- Label con soporte para campo requerido (asterisco rojo)
- Tipografía consistente

**`components/crm/card.blade.php`**
- Tarjeta contenedor con:
  - Título opcional
  - Padding configurable
  - Bordes y sombras del sistema Hando

**`components/crm/tabs.blade.php`**
- Sistema de pestañas horizontales
- 4 pestañas: About, Work Experience, Education, Settings
- Indicador visual azul para tab activo
- Manejo por query string (?tab=about)

### 3. Vistas Implementadas

#### Dashboard (`crm/dashboard.blade.php`)
- 4 tarjetas de estadísticas (Stats Cards):
  - Total Users
  - Revenue
  - Active Tasks
  - Conversion Rate
- Sección de actividad reciente
- Quick Actions panel
- Diseño responsive con grid de Tailwind

#### Profile Settings (`crm/profile/settings.blade.php`)
Vista principal con sistema de pestañas que incluye:

**Pestaña About** (`crm/profile/partials/about.blade.php`)
- Foto de perfil con opción de cambio
- Campos de nombre (First Name, Last Name)
- Email con ícono
- Teléfono con ícono
- Bio (textarea)
- Ubicación (City, Country)
- Botones de acción (Cancel, Save Changes)

**Pestaña Work Experience** (`crm/profile/partials/work-experience.blade.php`)
- Lista de experiencias laborales
- Cada item muestra:
  - Logo/Avatar de la empresa
  - Título del puesto
  - Nombre de la empresa
  - Período laboral
  - Descripción
  - Botones de editar/eliminar
- Botón "Add Experience"

**Pestaña Education** (`crm/profile/partials/education.blade.php`)
- Lista de estudios
- Similar a Work Experience:
  - Ícono de educación
  - Título del grado
  - Universidad
  - Período
  - Descripción/GPA
  - Botones de editar/eliminar
- Botón "Add Education"

**Pestaña Settings** (`crm/profile/partials/settings.blade.php`)
- **Account Settings:**
  - Email Notifications (toggle)
  - Push Notifications (toggle)
  - Two-Factor Authentication (botón Enable)
- **Privacy:**
  - Profile Visibility (radio buttons: Public, Private, Friends)
  - Show Activity Status (toggle)
- **Change Password:**
  - Current Password
  - New Password
  - Confirm New Password
- **Danger Zone:**
  - Delete Account (botón rojo)

### 4. Rutas Configuradas

```php
// Dashboard principal
Route::get('/dashboard', ...)->name('dashboard');

// Profile Settings con pestañas
Route::get('/crm/profile/settings', ...)->name('crm.profile.settings');

// Redirección automática de la ruta profile.edit a CRM
Route::get('/profile', ...)->name('profile.edit');
```

### 5. Dark Mode

El dark mode está implementado con:
- Alpine.js para manejo del estado
- localStorage para persistencia
- Clase `.dark` de Tailwind
- Toggle en el navbar
- Transiciones suaves entre modos

#### Uso del Toggle:
```html
<button @click="darkMode = !darkMode">
    <!-- Íconos que cambian según el estado -->
</button>
```

## Uso de Componentes

### Ejemplo: Card
```blade
<x-crm.card title="Mi Tarjeta">
    Contenido aquí
</x-crm.card>

<x-crm.card :padding="false">
    Contenido sin padding
</x-crm.card>
```

### Ejemplo: Button
```blade
<x-hando-button variant="primary" size="md">
    Guardar
</x-hando-button>

<x-hando-button variant="danger" size="sm">
    Eliminar
</x-hando-button>
```

### Ejemplo: Input con Ícono
```blade
<x-hando-input
    id="email"
    name="email"
    type="email"
>
    <x-slot name="icon">
        <svg>...</svg>
    </x-slot>
</x-hando-input>
```

### Ejemplo: Label
```blade
<x-hando-label for="name" value="Nombre" :required="true" />
```

## Estructura de Archivos

```
resources/views/
├── layouts/
│   └── crm.blade.php                    # Layout principal CRM
├── components/
│   ├── crm/
│   │   ├── sidebar.blade.php           # Sidebar de navegación
│   │   ├── navbar.blade.php            # Barra superior
│   │   ├── card.blade.php              # Componente Card
│   │   └── tabs.blade.php              # Sistema de pestañas
│   ├── hando-input.blade.php           # Input mejorado
│   ├── hando-button.blade.php          # Botón con variantes
│   └── hando-label.blade.php           # Label mejorado
├── crm/
│   ├── dashboard.blade.php             # Dashboard principal
│   └── profile/
│       ├── settings.blade.php          # Vista principal settings
│       └── partials/
│           ├── about.blade.php         # Pestaña About
│           ├── work-experience.blade.php  # Pestaña Work
│           ├── education.blade.php     # Pestaña Education
│           └── settings.blade.php      # Pestaña Settings
```

## Responsividad

El diseño es completamente responsive:
- Grid adaptativo con Tailwind (`md:grid-cols-2`, `lg:grid-cols-4`)
- Sidebar fijo en desktop (puede adaptarse para mobile con toggle)
- Cards que se apilan en mobile
- Espaciado consistente

## Próximos Pasos Sugeridos

1. **Implementar funcionalidad de formularios:**
   - Conectar los formularios con controladores Laravel
   - Validación de datos
   - Mensajes de éxito/error

2. **Mobile Sidebar:**
   - Agregar toggle para ocultar/mostrar sidebar en mobile
   - Overlay para cerrar al hacer click fuera

3. **Interactividad adicional:**
   - Modales para "Add Experience" y "Add Education"
   - Confirmación para "Delete Account"
   - Notificaciones toast

4. **Integración de datos reales:**
   - Conectar con modelos de Eloquent
   - Cargar datos dinámicos en Dashboard
   - CRUD completo para Work Experience y Education

5. **Optimizaciones:**
   - Lazy loading de imágenes
   - Code splitting con Vite
   - Caché de datos

## Recursos

- **Tailwind CSS**: https://tailwindcss.com/docs
- **Alpine.js**: https://alpinejs.dev/
- **Laravel Blade**: https://laravel.com/docs/blade
- **Heroicons** (para íconos SVG): https://heroicons.com/

## Notas Técnicas

- Se utiliza Alpine.js (ya incluido en Breeze) para interactividad
- El plugin `@alpinejs/collapse` se carga desde CDN para los menús expandibles
- Los colores personalizados están en el namespace `hando-*`
- La fuente principal es Inter (cargada desde Bunny Fonts)
