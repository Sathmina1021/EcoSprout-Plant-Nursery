@echo off
setlocal
cd /d %~dp0

set MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe
if not exist "%MYSQL_EXE%" set MYSQL_EXE=mysql

echo.
echo EcoSprout database reset/import for XAMPP
echo -----------------------------------------
echo This will recreate ecosprout_db using database.sql.
echo Make sure XAMPP MySQL is STARTED first.
echo.

"%MYSQL_EXE%" -u root -h 127.0.0.1 -P 3306 < database.sql
if %errorlevel%==0 goto success

echo.
echo Port 3306 failed. Trying port 3307...
"%MYSQL_EXE%" -u root -h 127.0.0.1 -P 3307 < database.sql
if %errorlevel%==0 goto success

echo.
echo Import failed.
echo 1. Start MySQL in XAMPP and make sure it is green.
echo 2. If MySQL cannot start, change MySQL port to 3307 in XAMPP.
echo 3. Then run this file again.
echo.
pause
exit /b 1

:success
echo.
echo Database imported successfully.
echo Open: http://localhost/ecosprout
echo Admin: admin@ecosprout.lk / Admin@123
echo.
pause
