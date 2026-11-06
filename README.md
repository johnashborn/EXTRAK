# EXTRACK

A prototype for tracking BISU extension services across campuses. The Extension Director submits a request, the Campus Director approves it, and the University President gives final approval. The service is then carried out, reported with photos, and kept in the archive. EXTRACK also flags similar work already done by other campuses.

This guide gets it running on your own computer, step by step. It assumes Windows with Laragon. It takes about 15 minutes the first time.

---

## 1. Install what you need

You need these once:

| What | Why | Where to get it |
|---|---|---|
| **Laragon** (Full edition) | Gives you PHP, MySQL and Composer in one install | [laragon.org/download](https://laragon.org/download/) |
| **Git** | To download the project | Comes with Laragon, or [git-scm.com](https://git-scm.com/) |

Check that Laragon's **PHP is 8.3 or newer**. In Laragon, right-click → **PHP** → **Version**. If it's older, download a newer PHP from [windows.php.net](https://windows.php.net/download/) (the x64 Thread Safe zip). Unzip it into `C:\laragon\bin\php\`, then pick it in that same menu.

The project also needs these PHP extensions: **intl, pdo_mysql, fileinfo, gd, zip**. To turn them on, right-click Laragon → **PHP** → **Extensions** and make sure each is checked.

> Not on Windows? Any setup with PHP 8.3+, Composer and MySQL (or MariaDB) works. The commands below are the same.

---

## 2. Get the code

**Option A: fork it (if you'll make changes).** On the GitHub page, click **Fork**. Then copy *your* fork's address from the green **Code** button.

**Option B: just clone it.** Use the original repository's address.

Open Laragon's **Terminal** (the **Terminal** button in Laragon) and run:

```bash
cd C:\laragon\www
git clone https://github.com/YOUR-USERNAME/EXTRAK.git
cd EXTRAK
```

Replace `YOUR-USERNAME` with your GitHub username. If you didn't fork, use `johnashborn`.

---

## 3. Set it up

Run these inside the `EXTRAK` folder, one at a time.

**3.1 Install the PHP packages.** The first time can take a few minutes.

```bash
composer install
```

**3.2 Create your settings file and app key.**

```bash
copy .env.example .env
php artisan key:generate
```

(On macOS or Linux, use `cp` instead of `copy`.)

**3.3 Start MySQL.** In Laragon, click **Start All**.

**3.4 Create the database.** In Laragon, click **Database**. It opens HeidiSQL; log in with user `root` and no password. Right-click the server on the left → **Create new** → **Database**, and name it `extract`.

Or, from the terminal:

```bash
mysql -u root -e "CREATE DATABASE extract"
```

> If your MySQL has a password, or you named the database differently, change `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in the `.env` file.

**3.5 Create the tables and the login account.**

```bash
php artisan migrate --seed
```

This creates every table, fills in the campuses, categories and barangays, and adds one login account (see step 5).

---

## 4. Run it

```bash
php artisan serve
```

Then open **http://127.0.0.1:8000** in your browser.

Keep that terminal open while you use the site. Press `Ctrl + C` to stop it. Next time, you only need **Start All** in Laragon and then `php artisan serve`.

---

## 5. Log in

Click **Sign in** on the landing page, or go to http://127.0.0.1:8000/admin/login.

| | |
|---|---|
| **Email** | `test@example.com` |
| **Password** | `password` |

Above the form, pick the role you want to see:

- **Extension Director**: writes requests and accomplishment reports for Balilihan Campus.
- **Extension Assistant**: encodes requests for the Extension Director (same dashboard).
- **Campus Director**: approves, returns or declines the campus's requests.
- **Campus Assistant**: helps the Campus Director (same dashboard).
- **President**: gives final approval and sees every campus.

To switch roles, sign out (bottom of the sidebar) and sign in again with another role.

**Sample data.** The first time any dashboard opens, it fills the database with sample requests, reports and notifications, so there's something to look at. Everyone using the same database sees the same data. Changes made in one role show up in the others within a few seconds.

**Walk one request through every role.** This is useful for screenshots:

1. Sign in as **Extension Director** → **New request**. Fill it in and submit it. While you type, EXTRACK checks every campus for similar work.
2. Sign out, then sign in as **Campus Director** → **Requests**. Open the request and **Approve** it.
3. Sign out, then sign in as **President**. Open it and give **final approval**.
4. Back as **Extension Director**, open it and click **Mark as started**. Then write the **accomplishment report** with photos. Submitting the report moves it to the **Archive**.

The President can also filter the archive and delete records from it.

**Demo controls (hidden by default).** There are testing controls that let one person act out the other roles (buttons like "Campus Director approves") and a **Reset demo data** button that puts the sample data back. They're hidden so the screens are clean for presenting. To turn them on, open `routes/web.php` and change `'demoControls' => false` to `true`. They show up in the sidebar and in each request's details.

---

## 6. Something went wrong?

| What you see | What to do |
|---|---|
| `SQLSTATE[HY000] [2002] No connection could be made` | MySQL isn't running. Click **Start All** in Laragon. |
| `Unknown database 'extract'` | You skipped step 3.4. Create the database, then run `php artisan migrate --seed`. |
| `composer` is not recognized | Use Laragon's **Terminal**, not the normal Windows one. |
| `Your requirements could not be resolved` / PHP version error | Your PHP is older than 8.3. See step 1. |
| `ext-intl` (or another `ext-…`) missing | Turn that extension on in Laragon → PHP → Extensions, then run `composer install` again. |
| `No application encryption key` | Run `php artisan key:generate`. |
| Login says the credentials don't match | Run `php artisan db:seed` to create the account again. |
| Page looks broken or old after pulling changes | Run `composer install`, `php artisan migrate` and `php artisan optimize:clear`, then refresh with `Ctrl + F5`. |
| Port 8000 is busy | Run `php artisan serve --port=8001` and open http://127.0.0.1:8001. |

---

## 7. Getting the latest changes

If you **cloned** the original repository:

```bash
git pull
composer install
php artisan migrate
```

If you **forked** it, first click **Sync fork** on your fork's GitHub page, then run the same three commands.

---

## What it's built with

Laravel 13 · Filament 3 (login) · Blade and Alpine.js (dashboards) · MySQL · Leaflet with OpenStreetMap (maps).

The campus budget allocation feature is built but turned off for now. Requests show their proposed budget as information only.
