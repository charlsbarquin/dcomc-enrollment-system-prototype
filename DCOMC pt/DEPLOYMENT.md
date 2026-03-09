# DCOMC System – Transfer to a New Computer/Server

Follow these steps to run the system on another blank Windows computer or server.

---

## Part 1: On the OLD computer (before transfer)

### Step 1.1 – Copy the project folder
- Copy the **entire project folder** (e.g. `DCOMC pt`) to a USB drive, shared folder, or zip it.
- You can **exclude** these to save space (they will be recreated on the new PC):
  - `vendor` (PHP dependencies)
  - `node_modules` (Node dependencies)
  - `public/build` (built frontend assets)
- **Include** everything else, especially:
  - `.env` (so you keep DB name and settings), **or** keep only `.env.example` and create a new `.env` on the new PC.

### Step 1.2 – (Optional) Export the database
- Open **HeidiSQL** and connect to your MySQL.
- Right‑click the database `dcomc_system` → **Export database as SQL**.
- Save the `.sql` file (e.g. `dcomc_system_backup.sql`) and copy it to the USB/folder with the project.
- On the new PC you will **create** the database and **import** this file so all data is there.

---

## Part 2: On the NEW computer – Install required software

Install these **in order**. Use default options unless you know you need something different.

### Step 2.1 – PHP 8.2 or higher
1. Download PHP for Windows: https://windows.php.net/download/
2. Get the **VS16 x64 Non Thread Safe** ZIP (or Thread Safe if you use Apache).
3. Extract to e.g. `C:\php`.
4. Add PHP to PATH:
   - Search **Environment Variables** in Windows → **Edit the system environment variables** → **Environment Variables**.
   - Under **System variables**, select **Path** → **Edit** → **New** → add `C:\php` (or your PHP folder).
5. (Optional) Copy `php.ini-development` to `php.ini` in the PHP folder and enable extensions Laravel needs:
   - Open `php.ini` and uncomment (remove `;`):  
     `extension_dir = "ext"`  
     `extension=fileinfo`  
     `extension=mbstring`  
     `extension=openssl`  
     `extension=pdo_mysql`  
     `extension=curl`  
     `extension=gd`  
     `extension=zip`

### Step 2.2 – Composer
1. Download: https://getcomposer.org/Composer-Setup.exe
2. Run the installer; it will detect PHP. Complete the setup.
3. Open a **new** Command Prompt and run: `composer --version` (should show a version number).

### Step 2.3 – MySQL or MariaDB
- **Option A – XAMPP:** Install XAMPP (includes MySQL and phpMyAdmin): https://www.apachefriends.org/
- **Option B – Standalone MySQL:** Install MySQL Community Server: https://dev.mysql.com/downloads/installer/
- **Option C – MariaDB:** https://mariadb.org/download/

During MySQL/MariaDB setup:
- Set **root password** (or leave blank to match your current `.env`).
- Note the port (usually **3306**).

### Step 2.4 – Node.js (LTS)
1. Download: https://nodejs.org/ (LTS version).
2. Run the installer (include “Add to PATH” if asked).
3. Open a **new** Command Prompt and run: `node --version` and `npm --version`.

### Step 2.5 – (Optional) HeidiSQL
- Download: https://www.heidisql.com/download.php  
- Use it to create the database and import your backup SQL if you exported it.

---

## Part 3: On the NEW computer – Put the project in place

### Step 3.1 – Copy project to the new PC
- Copy the project folder (e.g. `DCOMC pt`) to where you want it (e.g. `C:\Users\YourName\Desktop\DCOMC pt` or `D:\DCOMC pt`).

### Step 3.2 – Create the database (if you did not import a backup)
1. Open **HeidiSQL** (or phpMyAdmin if you use XAMPP).
2. Connect to MySQL (host: `127.0.0.1`, user: `root`, password: as you set).
3. Create a new database named: **`dcomc_system`** (same as in your `.env`).

**If you exported a backup:**  
- Create an empty database `dcomc_system`, then **Import** the `.sql` file you copied.

---

## Part 4: On the NEW computer – Configure and run the app

### Step 4.1 – Open Command Prompt in the project folder
- Press `Win + R`, type `cmd`, Enter.
- Go to the project folder, e.g.:
  ```bat
  cd "C:\Users\YourName\Desktop\DCOMC pt"
  ```
  (Use your actual path.)

### Step 4.2 – Use the setup script (easiest)
- Double‑click **`setup-new-pc.bat`** in the project folder, **or** in the same folder in CMD run:
  ```bat
  setup-new-pc.bat
  ```
- It will: install PHP dependencies, install Node dependencies, create `.env` from `.env.example` if missing, generate app key, run migrations, and build frontend assets.
- If it asks you to create the database, do that in HeidiSQL/phpMyAdmin first, then run the script again.

### Step 4.3 – Or do it manually
Run these in the project folder, one after another:

```bat
composer install
npm install
```

If there is **no** `.env` file:

```bat
copy .env.example .env
php artisan key:generate
```

Edit **`.env`** and set the database to match the new PC:

- `DB_DATABASE=dcomc_system`
- `DB_USERNAME=root`
- `DB_PASSWORD=` (or your MySQL root password)
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`

Then:

```bat
php artisan migrate
npm run build
```

(If you want seed data: `php artisan db:seed`.)

---

## Part 5: Run the system on the new PC

1. **Start MySQL** (XAMPP: start MySQL from control panel; standalone: it may already run as a service).
2. Double‑click:
   - **`start-local.bat`** – use only on this PC (browser opens to the app), or  
   - **`start-server.bat`** – allow other devices on the network to open the app.
3. To stop the server: run **`stop-system.bat`** or close the “Laravel Server” window.

---

## Quick checklist (new PC)

| Step | Done |
|------|------|
| PHP 8.2+ installed and in PATH | ☐ |
| Composer installed | ☐ |
| MySQL/MariaDB installed and running | ☐ |
| Node.js (LTS) installed | ☐ |
| Project folder copied to new PC | ☐ |
| Database `dcomc_system` created (or backup imported) | ☐ |
| `.env` exists and DB_* settings correct | ☐ |
| Ran `setup-new-pc.bat` (or manual composer/npm/migrate/build) | ☐ |
| Start with `start-local.bat` or `start-server.bat` | ☐ |

---

## Troubleshooting

- **“php is not recognized”**  
  PHP is not in PATH or Command Prompt was opened before installing PHP. Add PHP to PATH and open a **new** CMD.

- **“Access denied for user 'root'”**  
  In `.env`, set `DB_PASSWORD=` to your MySQL root password (or create a MySQL user that matches `.env`).

- **“SQLSTATE[HY000] [1049] Unknown database 'dcomc_system'”**  
  Create the database `dcomc_system` in HeidiSQL/phpMyAdmin and run again.

- **Port 8000 already in use**  
  Another app is using 8000. Close it or run: `php artisan serve --port=8001` and open `http://127.0.0.1:8001` in the browser.

- **Blank or broken CSS/JS (pages look plain, no Bootstrap)**  
  - Run `npm run build` again in the project folder.  
  - If you previously ran `npm run dev`, a file `public/hot` may still exist and will make the app load assets from the Vite dev server (which is not running). **Delete `public/hot`** so the app uses the built files in `public/build/` instead. Bootstrap is also loaded from a CDN so styling works even when the build is missing.
