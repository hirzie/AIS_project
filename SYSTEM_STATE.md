# System State & Architecture

## Tech Stack
- **Backend:** Native PHP 7.4/8.x (No Framework).
  - **Database Driver:** PDO (MySQL/MariaDB).
  - **Session Management:** Native PHP Sessions with `ais_init_session()`.
- **Frontend:** Vue.js 3 (Options API).
  - **Loading Strategy:** Hybrid (PHP Pre-fetch + Vue Hydration).
  - **Styling:** Tailwind CSS (CDN) + FontAwesome 5.
  - **Build Tool:** None (Raw Script Injection for Hosting Compatibility).
- **Database:** MySQL / MariaDB (InnoDB Engine).
- **Server:** Apache/Nginx (XAMPP for Local, cPanel/VPS for Prod).

## Architectural Patterns

### 1. Hybrid Rendering (Critical for Performance)
To avoid "Loading..." spinners and Layout Shift (CLS), we use a Hybrid approach:
1.  **PHP (Server-Side):** Fetches critical data (User Profile, Active Unit, Class Detail) during the initial HTTP request.
2.  **Injection:** Data is injected into global JS variables (`window.INITIAL_DATA`, `window.INITIAL_SETTINGS`) via `<script>` tags in the `<head>` or body.
3.  **Vue (Client-Side):** The Vue instance reads these global variables in `data()` or `created()` to hydrate the UI immediately without waiting for an AJAX API call.
4.  **Lazy Loading:** Secondary data (Notifications, Charts) is fetched via `fetch()` after mount.

### 2. Inline Script Strategy (Hosting Compatibility)
**Problem:** Many shared hosting environments block or mishandle ES Modules (`<script type="module">`) due to MIME type configuration or path resolution issues (`/assets/...` vs `./assets/...`).
**Solution:**
- We **DO NOT** use `.vue` files or `import ... from ...` syntax in production modules.
- We **INLINE** all component logic (Mixins) directly into the PHP file or load them as standard JS scripts.
- **Example:** `class_detail.php` contains the PHP logic, HTML template, and the Vue `createApp({...})` script in one file.

### 3. Service Layer Structure
- **`api/`**: Pure JSON endpoints.
  - strictly returns `application/json`.
  - handles DB operations via `config/database.php`.
- **`modules/`**: Feature-specific views (PHP + HTML + JS).
  - Enforced by `includes/guard.php`.
- **`includes/`**: Shared components.
  - `sidebar.php`: Navigation (Hybrid PHP/Vue).
  - `header.php`: Top bar & User controls.
  - `guard.php`: Security & Session validation.

## Critical Entry Points

### 1. `index.php` (The Dashboard)
- **Role:** Main SPA-like container for the "Core" module.
- **Key Features:**
  - Loads `sidebar.php` and `header.php`.
  - Handles internal routing for `dash`, `settings`, `profile`, `users`.
  - Inlines `adminMixin`, `academicMixin`, `utilsMixin` for instant interactivity.
  - **Performance:** Uses `skeleton-loading` CSS classes to prevent FOUC (Flash of Unstyled Content).

### 2. `login.php`
- **Role:** Authentication Gate.
- **Logic:**
  - Verifies credentials against `core_users`.
  - Sets `$_SESSION['user_id']`, `['role']`, `['allowed_modules']`, `['allowed_units']`.
  - Redirects based on Role (e.g., Teachers -> `index.php`, Parents -> `parent_portal.php`).

### 3. `config/database.php`
- **Role:** Database Connection Factory.
- **Logic:**
  - Detects Environment: `localhost` (root/root) vs `Production` (aiscore/password).
  - Handles `utf8mb4` charset.
  - Sets PDO Error Mode to Exception.

### 4. `includes/guard.php`
- **Role:** Security Firewall.
- **Logic:**
  - Checks `isset($_SESSION['user_id'])`.
  - Validates `$_SESSION['allowed_modules']` against the requested page.
  - Redirects unauthorized access to `index.php?error=noaccess`.
