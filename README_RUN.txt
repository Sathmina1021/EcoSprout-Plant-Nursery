EcoSprout Fully Fixed - XAMPP Run Guide
======================================

1) Extract this ZIP.
2) Copy the folder named "ecosprout" into:
   C:\xampp\htdocs\
3) Open XAMPP Control Panel.
4) Start Apache and MySQL. Both must be green.
5) Open this URL in your browser:
   http://localhost/ecosprout

Admin login
-----------
Email:    admin@ecosprout.lk
Password: Admin@123

Database / SQL
--------------
This version auto-creates and auto-imports the database on the first run.
The SQL file is included here:
   database.sql

If you want to reset/import manually:
1) Make sure XAMPP MySQL is green.
2) Double-click:
   RESET_AND_IMPORT_DATABASE.bat

Or import database.sql in phpMyAdmin.
The database.sql file is reset-safe for coursework/demo use: it recreates ecosprout_db with fresh sample data.

MySQL connection fixes included
-------------------------------
- Tries 127.0.0.1 and localhost.
- Tries port 3306 and port 3307 automatically.
- Uses XAMPP default user root with blank password.
- Shows clear help if MySQL is stopped or if host permissions are broken.

If you see ERROR 2002 / 10061
-----------------------------
That means MySQL is not running/listening.
Open XAMPP Control Panel and Start MySQL.
If MySQL cannot start, change MySQL port from 3306 to 3307 in:
XAMPP Control Panel -> Config -> Service and Port Settings -> MySQL.
Then start MySQL again and refresh http://localhost/ecosprout

Diagnostic page
---------------
Open this to check PHP -> MySQL connection:
http://localhost/ecosprout/db_check.php

If you see "Host localhost is not allowed"
------------------------------------------
1) Start MySQL in XAMPP.
2) Open XAMPP Shell.
3) Go to the project folder:
   cd C:\xampp\htdocs\ecosprout
4) Run:
   mysql -u root -h 127.0.0.1 -P 3306 < MYSQL_PERMISSION_FIX.sql

If your MySQL port is 3307, use:
   mysql -u root -h 127.0.0.1 -P 3307 < MYSQL_PERMISSION_FIX.sql

Fixed code notes
----------------
- Checkout/order SQL bind_param runtime error fixed.
- Dynamic SITE_URL/path support added.
- Missing pages added.
- Admin demo login hash fixed.
- Upload directories included.
- SQL seed data and admin account included.
- More robust XAMPP MySQL connection handling added.


Bundled image update:
- Uploaded plant photos are included in uploads/plants/.
- Services and blog posts now use local image files, so the frontend does not depend on external Unsplash links.
- New presentation screenshots are available in the separate EcoSprout_Frontend_Snippets folder/zip.


Demo Logins
-----------
Admin: admin@ecosprout.lk / Admin@123
Staff: staff@ecosprout.lk / Staff@123
User: user@ecosprout.lk / User@123

Plant Images
------------
All seed plants have matching local images under uploads/plants/.
