# Retrospectivas Scrum - Microservicios PHP

Aplicación para registrar y dar seguimiento a las reuniones de retrospectiva de un equipo Scrum. Permite administrar sprints, registrar logros, impedimentos, acciones y comentarios, revisar el cumplimiento de las acciones de sprints anteriores y consultar el historial.

Proyecto académico construido con **PHP + Slim 4 + Eloquent ORM + MySQL/MariaDB** (XAMPP), siguiendo la estructura de carpetas del proyecto de referencia `programacion_g1_s2_2026`.

---

## 1. Arquitectura

| Servicio | Puerto | Responsabilidad |
|---|---|---|
| `sprint-service` | 3001 | CRUD de sprints |
| `retrospective-service` | 3002 | CRUD de items de retrospectiva (`retro_items`) |
| `feedback-service` | 3003 | Consultas y resúmenes de logros, impedimentos y comentarios |
| `action-tracking-service` | 3004 | Seguimiento de acciones y acciones pendientes de sprints anteriores |
| Frontend (estático) | 8080 | Interfaz web |

Los cuatro servicios comparten la base de datos `registro_retro_db`, definida e inicializada desde `back/` (igual que en el proyecto del profesor, donde la configuración de Eloquent vive dentro de cada microservicio). En un entorno productivo cada microservicio tendría su propia base de datos; para este proyecto educativo se usa una sola base con el fin de mantener la simplicidad.

### Estructura del backend

```
back/
├── endpoints.http                # Peticiones listas para probar en VS Code (REST Client)
├── registro_retro_db.sql         # Esquema + datos de demostración
├── setup_database.php            # Inicializa la base con Eloquent
├── sprint-service/
├── retrospective-service/
├── feedback-service/
└── action-tracking-service/
```

### Estructura de cada microservicio

```
back/<servicio>/
├── app/
│   ├── Controllers/              # Lógica de negocio
│   ├── Models/                   # Modelos Eloquent
│   ├── Presentation/
│   │   ├── Repositories/         # Manejo de Request/Response (Slim)
│   │   └── Routers/endpoints.php # Definición de rutas
│   ├── Middlewares/cors.php      # Políticas CORS
│   └── global/database_config.php
├── public/index.php              # Punto de entrada
├── composer.json
└── vendor/                       # Dependencias instaladas con Composer
```

```
front/retro-app/
├── index.html                    # Redirige al dashboard
├── dashboard.html
├── sprints.html
├── retrospectiva.html
├── historial.html
├── item_form.html
├── css/styles.css
└── js/
    ├── api.js                    # Cliente de los 4 microservicios
    ├── inicio.js
    ├── sprints.js
    ├── retrospectiva.js
    └── historial.js
```

---

## 2. Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql`, `mbstring` y `json` (XAMPP ya las incluye).
- Composer 2.x.
- MySQL / MariaDB de XAMPP.
- Navegador moderno (Chrome, Edge o Firefox).

Verificar PHP y Composer:

```bat
php -v
composer --version
```

---

## 3. Base de datos

El esquema y los datos de ejemplo están en `back/registro_retro_db.sql`. Para crear la base y cargar los datos, ejecuta desde la raíz del proyecto:

```bat
php back\setup_database.php
```

El script usa Eloquent (Capsule) para aplicar el SQL y muestra cuántos sprints e items quedaron cargados. Es seguro repetirlo: no borra datos ni duplica los registros de ejemplo.

También puedes importarlo directamente:

```bat
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\Retrospectivas_Microservicios\back\registro_retro_db.sql"
```

Esto crea la base `registro_retro_db` y las tablas `sprints` y `retro_items`.

Credenciales configuradas en `app/global/database_config.php` de cada servicio:

| Parámetro | Valor |
|---|---|
| Host | `127.0.0.1` |
| Puerto | `3306` |
| Base de datos | `registro_retro_db` |
| Usuario | `root` |
| Contraseña | *(vacía)* |

Si su instalación de MySQL usa otra contraseña, modifique ese archivo en los cuatro servicios.

### Datos de demostración

La base de datos se entrega con dos sprints y once items de ejemplo incluidos en `back/registro_retro_db.sql`. Para volver a una base limpia:

```sql
USE registro_retro_db;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE retro_items;
TRUNCATE TABLE sprints;
SET FOREIGN_KEY_CHECKS = 1;
```

Para volver a cargar los datos de demostración:

```bat
php back\setup_database.php
```

---

## 4. Dependencias de Composer

Cada microservicio es un proyecto Composer independiente. Las dependencias ya están instaladas en `vendor/`, pero si descarga el proyecto de nuevo ejecute en cada carpeta de servicio:

```bat
cd back\sprint-service
composer install

cd ..\retrospective-service
composer install

cd ..\feedback-service
composer install

cd ..\action-tracking-service
composer install
```

Cada `composer.json` incluye:

```json
{
    "require": {
        "slim/slim": "4.*",
        "slim/psr7": "^1.8",
        "illuminate/database": "^9.52"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

Si modifica el autoload, ejecute `composer dump-autoload` dentro del servicio.

---

## 5. Ejecución

### Opción rápida (Windows)

Doble clic en `start_all.bat`. El script:

1. Inicia MySQL si no está corriendo.
2. Inicia los cuatro microservicios en los puertos 3001 a 3004.
3. Inicia el frontend en `http://127.0.0.1:8080` y abre el navegador.

### Opción manual

Abra una terminal por cada servicio:

```bat
cd back\sprint-service
php -S 127.0.0.1:3001 -t public
```

```bat
cd back\retrospective-service
php -S 127.0.0.1:3002 -t public
```

```bat
cd back\feedback-service
php -S 127.0.0.1:3003 -t public
```

```bat
cd back\action-tracking-service
php -S 127.0.0.1:3004 -t public
```

```bat
cd front\retro-app
php -S 127.0.0.1:8080 -t .
```

Abrir: `http://127.0.0.1:8080`

> El frontend debe servirse por HTTP (no abrir los archivos con `file://`), porque usa módulos JavaScript y peticiones `fetch`.

---

## 6. Endpoints

### Sprint Service (3001)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado del servicio |
| GET | `/sprints` | Listar sprints |
| GET | `/sprints/{id}` | Obtener un sprint |
| POST | `/sprints` | Crear sprint |
| PUT | `/sprints/{id}` | Actualizar sprint |
| DELETE | `/sprints/{id}` | Eliminar sprint y sus items |

Cuerpo para crear/actualizar:

```json
{
    "nombre": "Sprint 3 - Mejora continua",
    "fecha_inicio": "2026-11-01",
    "fecha_fin": "2026-11-14"
}
```

### Retrospective Service (3002)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado del servicio |
| GET | `/retro-items` | Listar todos los items |
| GET | `/retro-items?sprint_id=1&categoria=accion` | Filtrar items |
| GET | `/retro-items/sprint/{sprintId}` | Items de un sprint |
| GET | `/retro-items/sprint/{sprintId}/categoria/{categoria}` | Items por categoría |
| GET | `/retro-items/{id}` | Obtener un item |
| POST | `/retro-items` | Crear item |
| PUT | `/retro-items/{id}` | Actualizar item |
| DELETE | `/retro-items/{id}` | Eliminar item |

Categorías válidas: `logro`, `impedimento`, `accion`, `comentario`, `otro`.

Cuerpo para crear/actualizar:

```json
{
    "sprint_id": 1,
    "categoria": "accion",
    "descripcion": "Documentar los endpoints del proyecto",
    "cumplida": false,
    "fecha_revision": "2026-11-20"
}
```

`cumplida` y `fecha_revision` solo aplican a la categoría `accion`.

### Feedback Service (3003)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado del servicio |
| GET | `/feedbacks` | Feedback de todos los sprints (sin acciones) |
| GET | `/feedbacks/sprint/{sprintId}` | Logros, impedimentos y comentarios del sprint |
| GET | `/feedbacks/sprint/{sprintId}/type/{categoria}` | Feedback por categoría |
| GET | `/feedbacks/summary/{sprintId}` | Resumen y conteos del sprint |
| GET | `/feedbacks/stats` | Estadísticas globales |
| GET | `/feedbacks/{id}` | Obtener un item |

### Action Tracking Service (3004)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado del servicio |
| GET | `/action-tracking/sprint/{sprintId}` | Acciones del sprint |
| GET | `/action-tracking/pending/{sprintId}` | Acciones pendientes de sprints anteriores |
| GET | `/action-tracking/stats` | Estadísticas globales de acciones |
| GET | `/action-tracking/stats/{sprintId}` | Estadísticas de acciones del sprint |
| GET | `/action-tracking/history/{id}` | Detalle de una acción |
| GET | `/action-tracking/{id}` | Obtener una acción |
| PUT / PATCH | `/action-tracking/{id}` | Actualizar cumplimiento o fecha de revisión |

Cuerpo para actualizar:

```json
{
    "cumplida": true,
    "fecha_revision": "2026-11-21"
}
```

Ejemplos listos para usar en VS Code (extensión **REST Client** de `humansoftware.rest-client`): `back/endpoints.http`. Abra ese archivo en VS Code y pulse **Send Request** sobre cada petición; las variables `@sprint_host`, `@retro_host`, `@feedback_host` y `@action_host` están definidas al inicio.

---

## 7. Funcionalidades cubiertas

- Crear y administrar sprints.
- Registrar logros, impedimentos, acciones y comentarios por sprint.
- Editar y eliminar cualquier item de retrospectiva.
- Consultar el historial de retrospectivas por sprint.
- Cargar las acciones pendientes de sprints anteriores y marcar su cumplimiento.
- Ver estadísticas de cumplimiento en el dashboard.
- Sin inicio de sesión, según lo indicado en la definición del proyecto.

---

## 8. Solución de problemas

| Problema | Solución |
|---|---|
| `SQLSTATE[HY000] [2002]` | MySQL no está iniciado. Ejecute `start_all.bat` o inicie MySQL desde XAMPP. |
| El frontend no carga datos | Verifique que los 4 servicios estén activos (indicadores del dashboard). |
| Error de CORS | Los servicios ya incluyen el middleware CORS. Reinicie el servicio si modificó `cors.php`. |
| `vendor/autoload.php` no encontrado | Ejecute `composer install` en el servicio correspondiente. |
| Las fechas se muestran en otro formato | La API devuelve `AAAA-MM-DD`; el frontend las formatea como `DD/MM/AAAA`. |
