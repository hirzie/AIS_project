# Database Deep Dive (MySQL / MariaDB)

## Database Lifecycle

### 1. Initialization
- **Scripts:** `install_db.php` (Core), `install_db_full.php` (Modules).
- **Process:**
  - Checks if database exists (Create if not).
  - Creates tables in dependency order (Core -> HR -> Academic -> Finance).
  - Seeds initial data:
    - Default User: `admin` / `admin`.
    - Roles: `SUPERADMIN`, `TEACHER`, `STUDENT`.
    - Settings: `school_name`, `logo_url`.

### 2. Backup & Restore Procedures
- **Module:** `modules/admin/backup.php`
- **Backup Strategy:**
  1.  **CLI Method (Primary):** Tries `mysqldump` command via `exec()`.
  2.  **PHP Fallback (Secondary):** Loops through tables, fetches rows in batches (1000), and streams SQL `INSERT` statements to a file.
  3.  **Storage:** Saves `.sql` files in `AISbackup/` directory with timestamped filenames.
- **Restore Strategy:**
  1.  **CLI Method (Primary):** Tries `mysql` command via `exec()`.
  2.  **PHP Stream Loop (Secondary):** Reads `.sql` file line-by-line, executing statements immediately. Uses `set_time_limit(0)` and `ob_flush()` (keep-alive) to prevent timeout on large dumps.

### 3. Schema Management
- **Table:** `sys_migrations`
  - Tracks applied migration files.
  - Columns: `id`, `version`, `migration_name`, `executed_at`.
- **Table:** `schema_checkpoints`
  - Stores JSON snapshot of expected table structure.
  - Used for drift detection (Dev vs Prod).

## Critical Relationships

### Core System
- **`core_people`**: Central Identity Table (Students, Teachers, Staff).
  - `id` (PK), `name`, `type` (ENUM).
- **`core_users`**: Authentication Table.
  - `id` (PK), `username`, `password` (Hash), `role`, `people_id` (FK -> `core_people.id`).
  - **Relationship:** 1 Person can have 0 or 1 User Account.

### Academic Module
- **`acad_classes`**: Groups students.
  - `id` (PK), `name`, `level_id`, `homeroom_teacher_id` (FK -> `core_people.id`).
- **`acad_student_classes`**: Enrollment History.
  - `id` (PK), `student_id` (FK), `class_id` (FK), `academic_year_id`, `status` (Active/Moved).
  - **Constraint:** A student can be `ACTIVE` in only one class per academic year.

### Finance Module (Double-Entry)
- **`finance_accounts`**: Chart of Accounts (COA).
  - `id` (PK), `code`, `name`, `type` (Asset, Liability, Equity, Revenue, Expense).
- **`finance_journal_entries`**: Ledger.
  - `id` (PK), `transaction_date`, `description`, `reference_id` (e.g., Invoice ID).
- **`finance_journal_items`**: Splits.
  - `id` (PK), `journal_entry_id` (FK), `account_id` (FK), `debit`, `credit`.
  - **Constraint:** Sum(Debit) must equal Sum(Credit) per `journal_entry_id`.

## Idempotency & Data Integrity

### 1. Finance Transactions
- **Problem:** Double payment on slow networks.
- **Solution:** `request_id` (UUID) sent from frontend.
- **Implementation:**
  - `finance_journal_entries` has a `UNIQUE` index on `request_id`.
  - API checks `INSERT IGNORE` or catches Duplicate Key Error.

### 2. Zero Report (Security)
- **Problem:** Duplicate incident reports.
- **Solution:** Similar `request_id` mechanism.

### 3. Triggers (Recommended / Implemented)
- **`AFTER INSERT` on `acad_student_classes`:**
  - Increments `acad_classes.student_count`.
- **`BEFORE DELETE` on `core_people`:**
  - Checks for foreign key references in `finance_transactions` or `acad_grades` to prevent orphan records.
