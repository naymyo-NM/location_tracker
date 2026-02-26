# Deploying to Render

## 1. PostgreSQL database

You already have a Render PostgreSQL instance. Use:

- **Internal URL** (when the Laravel app runs on Render):  
  `postgresql://tracking_app_x455_user:pMsEmINBtrQk1JWBM3H1tuZVjwr6fgFp@dpg-d6fsumn5r7bs73f3vamg-a/tracking_app_x455`

- **External URL** (from your machine or Flutter app):  
  `postgresql://tracking_app_x455_user:pMsEmINBtrQk1JWBM3H1tuZVjwr6fgFp@dpg-d6fsumn5r7bs73f3vamg-a.singapore-postgres.render.com/tracking_app_x455`

For the **Laravel Web Service on Render**, set `DATABASE_URL` to the **internal** URL (no port in host; Render resolves it).

## 2. Create Web Service

1. In [Render Dashboard](https://dashboard.render.com), **New → Web Service**.
2. Connect the repo that contains this Laravel app (e.g. `Location_tracking` as root, or set **Root Directory** to `Location_tracking` if the repo is the whole `Tracking` folder).
3. **Runtime**: Docker.
4. **Build**: Render uses the repo `Dockerfile` (no extra command needed).
5. **Environment variables** (required):

   | Key            | Value / action |
   |----------------|-----------------|
   | `DATABASE_URL` | Paste the **internal** PostgreSQL URL above (or from your Render DB’s “Internal Database URL”). |
   | `DB_CONNECTION`| `pgsql` (already in `render.yaml` if you use Blueprint). |
   | `APP_KEY`      | Generate: `php artisan key:generate --show` and paste, or use Render’s “Generate” if available. |
   | `APP_URL`      | Your Render service URL, e.g. `https://tracking-api-xxxx.onrender.com` (no trailing slash). |

6. Save and deploy. The Docker build runs `composer install`, then on container start: `config:cache`, `route:cache`, `migrate --force`, then Nginx/PHP-FPM.

## 3. Queue and scheduler (optional)

- App uses **database** queue and **database** sessions/cache by default (no Redis required).
- To run the batch job and scheduler, add a **Background Worker** on Render (same repo, same Dockerfile) with:
  - **Start command**: `php artisan schedule:work` (or run `schedule:run` via cron if you add a cron service).
- If you don’t add a worker, live tracking and single-point tracking still work; batch write and scheduled jobs won’t run until you do.

## 4. Flutter app

In the Flutter app set the API base URL to your Render web service URL, e.g.:

`https://tracking-api-xxxx.onrender.com/api`

(Update `api_config.dart` or your env so the app uses this in production.)

## 5. Blueprint (optional)

A `render.yaml` is included. You can create the Web Service from the Blueprint (Dashboard → New → Blueprint) and then set `DATABASE_URL`, `APP_KEY`, and `APP_URL` in the service’s Environment tab (marked `sync: false`).

## 6. Database queue tables

If you use `QUEUE_CONNECTION=database`, ensure the `jobs` (and optionally `job_batches`) tables exist. One-time, locally:

```bash
php artisan queue:table
php artisan queue:batches-table
php artisan migrate
```

Commit the new migrations and deploy so `migrate --force` on Render creates these tables.
