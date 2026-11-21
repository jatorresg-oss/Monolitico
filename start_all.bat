@echo off
setlocal

set "ROOT=%~dp0"
set "MYSQL_BIN=C:\xampp\mysql\bin"
set "PHP_BIN=C:\xampp\php\php.exe"

echo ============================================================
echo  Retrospectivas Scrum - Inicio de servicios
echo ============================================================

"%MYSQL_BIN%\mysqladmin.exe" -u root ping >nul 2>&1
if not errorlevel 1 goto mysql_ready

echo Iniciando MySQL...
start "MySQL - XAMPP" /min "%MYSQL_BIN%\mysqld.exe" --defaults-file="%MYSQL_BIN%\my.ini" --standalone
set MYSQL_TRIES=0
:wait_mysql
"%MYSQL_BIN%\mysqladmin.exe" -u root ping >nul 2>&1
if not errorlevel 1 goto mysql_ready
set /a MYSQL_TRIES+=1
if %MYSQL_TRIES% GEQ 20 goto mysql_failed
rem "ping" espera sin consumir stdin (a diferencia de "timeout")
ping -n 2 127.0.0.1 >nul
goto wait_mysql

:mysql_failed
echo ERROR: MySQL no respondio tras 40 segundos.
echo Revisa C:\xampp\mysql\data\mysql_error.log
goto end

:mysql_ready
echo MySQL listo.

echo Iniciando microservicios...
start "Sprint Service - 3001" /D "%ROOT%back\sprint-service" cmd /k "%PHP_BIN% -S 127.0.0.1:3001 -t public"
start "Retrospective Service - 3002" /D "%ROOT%back\retrospective-service" cmd /k "%PHP_BIN% -S 127.0.0.1:3002 -t public"
start "Feedback Service - 3003" /D "%ROOT%back\feedback-service" cmd /k "%PHP_BIN% -S 127.0.0.1:3003 -t public"
start "Action Tracking Service - 3004" /D "%ROOT%back\action-tracking-service" cmd /k "%PHP_BIN% -S 127.0.0.1:3004 -t public"

echo Iniciando frontend...
start "Frontend - 8080" /D "%ROOT%front\retro-app" cmd /k "%PHP_BIN% -S 127.0.0.1:8080 -t ."

ping -n 4 127.0.0.1 >nul
start "" "http://127.0.0.1:8080"

echo.
echo Servicios iniciados:
echo   Frontend.............. http://127.0.0.1:8080
echo   Sprint Service........ http://127.0.0.1:3001/health
echo   Retrospective Service. http://127.0.0.1:3002/health
echo   Feedback Service...... http://127.0.0.1:3003/health
echo   Action Tracking....... http://127.0.0.1:3004/health
echo.
echo Cierre las ventanas de los servicios para detenerlos.

:end
endlocal
