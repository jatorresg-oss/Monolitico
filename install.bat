@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo ============================================================
echo Instalando Retrospectivas Microservicios
echo ============================================================
echo.

REM Verificar que estamos en el directorio correcto
if not exist "back\sprint-service\composer.json" (
    echo ERROR: Ejecute este script desde la raiz del proyecto
    echo (donde esta install.bat)
    pause
    exit /b 1
)

REM Verificar PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: No se encuentra php.exe en PATH
    echo Agregue C:\xampp\php al PATH del sistema
    pause
    exit /b 1
)

REM Verificar MySQL
set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe
if not exist "%MYSQL_PATH%" (
    echo ERROR: No se encuentra mysql.exe en %MYSQL_PATH%
    echo Ajuste la variable MYSQL_PATH en este script
    pause
    exit /b 1
)

echo [1/5] Instalando dependencias de Sprint Service...
cd back\sprint-service
composer install --no-interaction
if %errorlevel% neq 0 (
    echo ERROR instalando Sprint Service
    pause
    exit /b 1
)
cd ..\..

echo [2/5] Instalando dependencias de Retrospective Service...
cd back\retrospective-service
composer install --no-interaction
if %errorlevel% neq 0 (
    echo ERROR instalando Retrospective Service
    pause
    exit /b 1
)
cd ..\..

echo [3/5] Instalando dependencias de Feedback Service...
cd back\feedback-service
composer install --no-interaction
if %errorlevel% neq 0 (
    echo ERROR instalando Feedback Service
    pause
    exit /b 1
)
cd ..\..

echo [4/5] Instalando dependencias de Action Tracking Service...
cd back\action-tracking-service
composer install --no-interaction
if %errorlevel% neq 0 (
    echo ERROR instalando Action Tracking Service
    pause
    exit /b 1
)
cd ..\..

echo [5/5] Creando la base de datos y las tablas...
echo.
echo Creando registro_retro_db con sprints y retro_items...
"%MYSQL_PATH%" -u root < "back\sprint-service\database\schema.sql"
if errorlevel 1 (
    echo ERROR creando registro_retro_db. Verifique que MySQL este corriendo.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo Instalación completada!
echo ============================================================
echo.
echo Para iniciar los servicios, ejecute:
echo   start_all.bat
echo.
echo O manualmente (en 4 terminales separadas):
echo   cd back\sprint-service && php -S 127.0.0.1:3001 -t public
echo   cd back\retrospective-service && php -S 127.0.0.1:3002 -t public
echo   cd back\feedback-service && php -S 127.0.0.1:3003 -t public
echo   cd back\action-tracking-service && php -S 127.0.0.1:3004 -t public
echo.
echo Frontend (Apache): http://localhost/Retrospectivas_Microservicios/front/retro-app/
echo.
pause