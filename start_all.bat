@echo off
chcp 65001 >nul
echo ============================================================
echo Iniciando Microservicios - Retrospectivas Agiles
echo ============================================================
echo.
echo Asegurate de tener MySQL (XAMPP) corriendo en el puerto 3306
echo.

set PHP_PATH=C:\xampp\php\php.exe
if not exist "%PHP_PATH%" (
    echo ERROR: No se encuentra php.exe en %PHP_PATH%
    pause
    exit /b 1
)

echo [1/4] Iniciando Sprint Service (puerto 3001)...
start "Sprint Service" cmd /k "%PHP_PATH%" -S 127.0.0.1:3001 -t back\sprint-service\public

echo [2/4] Iniciando Retrospective Service (puerto 3002)...
start "Retrospective Service" cmd /k "%PHP_PATH%" -S 127.0.0.1:3002 -t back\retrospective-service\public

echo [3/4] Iniciando Feedback Service (puerto 3003)...
start "Feedback Service" cmd /k "%PHP_PATH%" -S 127.0.0.1:3003 -t back\feedback-service\public

echo [4/4] Iniciando Action Tracking Service (puerto 3004)...
start "Action Tracking Service" cmd /k "%PHP_PATH%" -S 127.0.0.1:3004 -t back\action-tracking-service\public

echo.
echo ============================================================
echo ¡Todos los servicios iniciados!
echo ============================================================
echo.
echo Sprint Service:        http://localhost:3001
echo Retrospective Service: http://localhost:3002
echo Feedback Service:      http://localhost:3003
echo Action Tracking:       http://localhost:3004
echo.
echo Frontend (Apache):     http://localhost/Retrospectivas_Microservicios/front/retro-app/
echo.
echo Presiona Ctrl+C en cada ventana para detener los servicios.
echo.
pause