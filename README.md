# Contacts CRM (Laravel 12)

This repository contains a lightweight CRM-style module built with Laravel that focuses on managing contacts, their custom fields, and merge workflows. Everything runs with AJAX-powered interactions so the UI feels responsive without full page reloads.

## Feature Highlights

- **Contact CRUD with AJAX:** Create, update, archive (soft delete) and list contacts without leaving the current page. File uploads (profile image & supporting document) are stored on the public disk.
- **Dynamic Custom Fields:** Administrators can create custom field definitions (text, number, date, select). Fields render automatically in the contact form and values are stored per contact.
- **Multi-email & multi-phone support:** Primary values stay in the `contacts` table; additional entries are normalized in supporting tables during merge operations.
- **Merge workflow:** Select two contacts, preview differences, and merge them. The secondary record is preserved (soft deleted) and a `contact_merges` audit entry is written.
- **Filtering & search:** Filter contacts by name, email (including secondary emails), gender, or custom field values via AJAX requests.
- **No authentication yet:** An admin/login module has **not** been implemented. The entire UI assumes trusted access.

## Tech Stack

- PHP 8.2+
- Laravel 12.x
- MySQL (configured for `newss_crm` database)
- Tailored Blade templates with vanilla JavaScript for interactivity

## Database Overview

The schema focuses on flexibility for contact data and merge auditing.

| Table | Purpose | Key Columns |
| --- | --- | --- |
| `contacts` | Primary contact record. Soft deletes preserve archived/merged entries. | `name`, `email`, `phone`, `gender`, `profile_image_path`, `document_path`, `status`, `merged_into_id`, `merge_summary` (JSON) |
| `contact_emails` | Stores additional emails per contact (with primary flag + origin contact reference for merges). | `contact_id`, `email`, `is_primary`, `origin_contact_id` |
| `contact_phones` | Stores additional phone numbers per contact. | `contact_id`, `phone`, `is_primary`, `origin_contact_id` |
| `custom_fields` | Definition of dynamic fields administrators can create. | `name`, `slug`, `field_type` (`text`, `number`, `date`, `select`), `options` (JSON), `is_required`, `is_active` |
| `contact_custom_field_values` | Captures contact-specific values for each custom field. | `contact_id`, `custom_field_id`, `value`, `is_primary`, `origin_contact_id` |
| `contact_merges` | Audit log of every merge, including snapshots of merged attributes/custom fields/files. | `master_contact_id`, `secondary_contact_id`, `merged_attributes` (JSON), `merged_custom_fields` (JSON), `merged_files` (JSON), `status`, `merged_at` |

> **Note:** All supporting tables use soft deletes where appropriate so merged/archived data is never permanently removed.

## Getting Started

### 1. Clone & Install Dependencies

```bash
git clone <repo-url> contacts-crm
cd contacts-crm
composer install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update the `.env` file with your local database credentials. The project expects a MySQL database named `newss_crm`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=newss_crm
DB_USERNAME=root
DB_PASSWORD=    # update if your MySQL has a password
```

Create the database if it does not exist yet:

```sql
CREATE DATABASE newss_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Run Migrations

```bash
php artisan migrate
```

> There are no seeders yet—create contacts/custom fields through the UI after running migrations.

### 4. Link Storage (for uploaded files)

```bash
php artisan storage:link
```

### 5. Serve the Application

```bash
php artisan serve
```

Access the UI at `http://127.0.0.1:8000`.

## Useful Artisan Commands

| Command | Description |
| --- | --- |
| `php artisan migrate:fresh` | Drop all tables and re-run migrations from scratch. |
| `php artisan migrate --seed` | Run migrations (and seeds if added later). |
| `php artisan storage:link` | Expose `storage/app/public` for file uploads. |
| `php artisan test` | Execute automated tests (none included yet, but harness ready). |
| `php artisan make:model` / `php artisan make:migration` | Generate additional models/migrations as you extend the CRM. |

## Current Limitations & Next Steps

- **Authentication is missing** – build an admin/login flow before deploying to production environments.
- **Validation enhancements** – consider adding stronger custom field validation (e.g., select options enforcement) and server-side file size constraints.
- **Automated tests** – create feature tests to cover merge workflows, filters, and custom field handling.
- **Role-based access** – once auth is added, restrict merge/custom field management to admin roles.