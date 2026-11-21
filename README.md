
# Retrospectivas Ágiles

## Ejecutar localmente

La aplicación requiere que exista la tabla `sprints` en `registro_retro_db`. Inicializa las tablas de retrospectivas y feedback desde la raíz del repositorio:

```bat
C:\xampp\mysql\bin\mysql.exe -u root < back\retrospective-service\database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root < back\feedback-service\database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root < back\action-tracking-service\database\schema.sql
```

Inicia cada API en una terminal independiente:

```bat
cd back\sprint-service\public
php -S 127.0.0.1:3001
```

```bat
cd back\retrospective-service\public
php -S 127.0.0.1:3002
```

```bat
cd back\feedback-service\public
php -S 127.0.0.1:3003
```

```bat
cd back\action-tracking-service\public
php -S 127.0.0.1:3004
```

Sirve `front\retro-app` con Apache de XAMPP y abre `http://localhost/Retrospectivas_Microservicios/front/retro-app/`.
