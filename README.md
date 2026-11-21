# Retrospectivas Ágiles - Microservicios

Aplicación para registro y seguimiento de retrospectivas Scrum, construida con **arquitectura de microservicios** (4 servicios independientes + frontend vanilla JS) usando **una sola base de datos compartida** para simplicidad en XAMPP.

## Arquitectura

| Servicio | Puerto | Responsabilidad |
|----------|--------|-----------------|
| `sprint-service` | 3001 | CRUD Sprints + Teams |
| `retrospective-service` | 3002 | CRUD Retrospectivas + Participantes |
| `feedback-service` | 3003 | Ítems: Logros, Impedimentos, Acciones, Comentarios |
| `action-tracking-service` | 3004 | Seguimiento de cumplimiento de acciones entre sprints |

Todos los servicios comparten la base de datos **`retrospectivas_db`** (se crea automáticamente).

```
┌─────────────────────────────────────────────────────────────────┐
                        FRONTEND (JS Vanilla)                     
                    http://localhost/retro-app/                   
└─────────────────────────────────────────────────────────────────┘
         │                    │                    │
    ┌────┴────┐          ┌────┴────┐          ┌────┴────┐
    ▼         ▼          ▼         ▼          ▼         ▼
┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐
│ Sprint  │ │Retro-   │ │Feedback │ │Action   │ │ Shared  │
│ Service │ │spective │ │ Service │ │Tracking │ │  DB     │
│  :3001  │ │Service  │ │  :3003  │ │ :3004   │ │retros_  │
└─────────┘ └─────────┘ └─────────┘ └─────────┘ │pectivas_│
    │         │         │         │              │   db    │
    └─────────┴─────────┴─────────┴──────────────┘         │
                              │                  
                    ┌─────────┴─────────┐
                    ▼                   ▼
            ┌───────────────┐   ┌───────────────┐
            │ MySQL         │   │ MySQL         │
            │ (retrospectivas_db)             │
            │ Compartida    │   │ (esquemas)    │
            └───────────────┘   └───────────────┘
```

## Requisitos

- **XAMPP** con PHP 8.1+ y MySQL 8.0+
- **Composer** (para instalar dependencias)
- **Apache** (para servir el frontend)
- Puertos libres: **3001, 3002, 3003, 3004**

## Instalación Rápida (Windows)

### 1. Instalar dependencias de cada servicio
```bash
cd back\sprint-service && composer install
cd ..\retrospective-service && composer install
cd ..\feedback-service && composer install
cd ..\action-tracking-service && composer install
```

### 2. Instalación automática (recomendada)
```bash
install.bat
```
Este script:
- Instala dependencias de los 4 servicios
- Crea la BD `retrospectivas_db` y todas las tablas
- Carga datos de prueba (seed data)

### 3. Iniciar servicios
```bash
start_all.bat
```
O manualmente (4 terminales separadas):
```bash
cd back\sprint-service && php -S 127.0.0.1:3001 -t public
cd back\retrospective-service && php -S 127.0.0.1:3002 -t public
cd back\feedback-service && php -S 127.0.0.1:3003 -t public
cd back\action-tracking-service && php -S 127.0.0.1:3004 -t public
```

### 4. Frontend (Apache)
Configurar VirtualHost en `httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/Retrospectivas_Microservicios/front/retro-app"
    ServerName retro-app.local
    <Directory "C:/xampp/htdocs/Retrospectivas_Microservicios/front/retro-app">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
En `C:\Windows\System32\drivers\etc\hosts`: `127.0.0.1 retro-app.local`

Acceder a: **http://retro-app.local/** (o `http://localhost/Retrospectivas_Microservicios/front/retro-app/`)

## Uso

1. **Dashboard**: Ver stats + sprints recientes
2. **Sprints**: Crear/editar sprints con fechas y equipo
3. **Retrospectiva**: Seleccionar sprint → 4 columnas:
   - Logros (positive)
   - Impedimentos (negative)
   - Acciones (action) → con checkbox cumplida + fecha revisión
   - Comentarios (comment)
   + Acciones anteriores (checkbox para marcar cumplidas)
4. **Historial**: Filtrar por sprint → ver todo agrupado por categoría

## Endpoints API

Ver `back/endpoints.http` para ejemplos completos con REST Client (VS Code).

### Sprint Service (3001)
```
GET    /sprints                    # Listar sprints
GET    /sprints/{id}               # Obtener sprint
POST   /sprints                    # Crear sprint
PUT    /sprints/{id}               # Actualizar sprint
DELETE /sprints/{id}               # Eliminar sprint
```

### Retrospective Service (3002)
```
GET    /retrospectives                     # Listar retrospectivas
GET    /retrospectives/sprint/{sprintId}   # Retrospectiva por sprint
POST   /retrospectives                     # Crear retrospectiva
PUT    /retrospectives/{id}                # Actualizar
PUT    /retrospectives/{id}/complete       # Marcar completada
```

### Feedback Service (3003)
```
GET    /feedbacks/retrospective/{retroId}              # Items de retrospectiva
GET    /feedbacks/retrospective/{retroId}/type/{type}  # Filtrar por tipo
POST   /feedbacks                                      # Crear item
POST   /feedbacks/bulk                                 # Crear múltiples
PUT    /feedbacks/{id}                                 # Actualizar
DELETE /feedbacks/{id}                                 # Eliminar
```
Tipos: `positive` (Logro), `negative` (Impedimento), `action` (Acción), `comment` (Comentario)

### Action Tracking Service (3004)
```
GET    /action-tracking/pending/{sprintId}    # Acciones pendientes de revisar
GET    /action-tracking/sprint/{sprintId}     # Seguimiento en sprint
GET    /action-tracking/history/{feedbackId}  # Historial de una acción
POST   /action-tracking                       # Registrar cumplimiento
```
Estados: `completed`, `partial`, `not_completed`, `carried_over`

## Estructura de Carpetas

```
Retrospectivas_Microservicios/
├── install.bat                 # Instalación automática
├── start_all.bat               # Iniciar todos los servicios
├── .gitignore
├── README.md
├── back/
│   ├── endpoints.http          # Pruebas API (REST Client VS Code)
│   ├── sprint-service/         # Puerto 3001
│   ├── retrospective-service/  # Puerto 3002
│   ├── feedback-service/       # Puerto 3003
│   └── action-tracking-service/ # Puerto 3004
│       ├── app/
│       │   ├── Controllers/
│       │   ├── Models/
│       │   ├── Middlewares/cors.php
│       │   ├── Presentation/
│       │   │   ├── Repositories/
│       │   │   └── Routers/routes.php
│       │   └── global/database_config.php
│       ├── public/index.php
│       ├── database/schema.sql
│       ├── composer.json/.lock
│       └── vendor/
└── front/
    └── retro-app/              # Frontend JS Vanilla
        ├── index.html → dashboard.html
        ├── dashboard.html
        ├── sprints.html
        ├── retrospectiva.html
        ├── historial.html
        ├── item_form.html
        ├── css/styles.css
        └── js/
            ├── api.js
            ├── inicio.js
            ├── sprints.js
            ├── retrospectiva.js
            └── historial.js
```

## Desarrollo

### Agregar nuevo endpoint
1. Crear método en `Controller`
2. Agregar método en `Repository` 
3. Registrar ruta en `Router/routes.php`
4. Probar con `back/endpoints.http`

### Convenciones
- **PSR-4** autoloading (`App\\` namespace)
- **PSR-12** código estilo
- **RESTful** APIs con JSON
- **UTF-8** encoding
- **CORS** habilitado en todos los servicios

## Troubleshooting

| Problema | Solución |
|----------|----------|
| Puerto ocupado | Cambiar puerto en `start_all.bat` y `api.js` |
| CORS error | Verificar middleware CORS en cada servicio |
| BD no conecta | Verificar que MySQL esté corriendo en XAMPP (puerto 3306) |
| Frontend no carga | Verificar Apache DocumentRoot y mod_rewrite |
| Composer error | `composer install --ignore-platform-reqs` |
| Tablas no crean | Verificar permisos de usuario root en MySQL |

## Licencia

Proyecto educativo - Uso libre para aprendizaje de Scrum y microservicios.