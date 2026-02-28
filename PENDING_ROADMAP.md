# Pending Roadmap & Next Steps

## Immediate Tasks (Next Agent)

### 1. Hybrid Rendering Expansion
- **Goal:** Apply the "Inline Script + PHP Pre-fetch" pattern to:
  - `modules/finance/dashboard.php` (Currently slow/loading spinner).
  - `modules/inventory/dashboard.php` (Complex asset tables).
  - `modules/counseling/index.php` (Incident management).
- **Why:** To improve LCP (Largest Contentful Paint) and reduce layout shifts across the entire application.

### 2. Full Google Gemini AI Integration
- **Goal:** Move beyond just storing the API Key in `core_settings`.
- **Plan:**
  - Create `api/ai_chat.php` to handle prompts.
  - Add an AI Assistant Chat Widget to the bottom-right corner of `index.php`.
  - Implement specific AI actions:
    - "Summarize this student's disciplinary record."
    - "Generate a quiz for Math class based on this topic."
    - "Analyze financial trends for Q1."

### 3. Unified Navigation Logic
- **Goal:** Refactor `includes/sidebar.php` to fully support the dynamic JSON structure defined in `index.php`.
- **Current State:** The PHP sidebar uses a static array `$menuStructurePHP` which must be manually synchronized with the Vue `menuStructure`.
- **Plan:**
  - Move the menu definition to a single `config/menu.php` file that returns an array.
  - Make both PHP (Sidebar) and Vue (Dashboard) consume this single source of truth.

## Known Bugs / Issues
- **1. Schedule Generator Race Condition:**
  - `generate_schedule.php` does not lock the database tables during execution. If two users click "Shuffle" simultaneously, duplicate schedules might be created.
  - **Fix:** Implement `LOCK TABLES` or Transaction Isolation.
- **2. Mobile Table Responsiveness (Finance):**
  - The `finance_transactions` table overflows horizontally on mobile devices.
  - **Fix:** Add horizontal scrolling wrapper or switch to card view for mobile.
- **3. Hardcoded Unit Logic in Reports:**
  - Some reports (e.g., `modules/academic/print_report.php`) have `IF unit = 'SD'` logic hardcoded.
  - **Fix:** Refactor to use dynamic `unit_code` from database.

## Future Enhancements
- **Parent Portal PWA:** Convert the parent view into a Progressive Web App for offline access and push notifications.
- **Biometric Integration:** Link `modules/hr/attendance.php` to fingerprint/face recognition hardware API.
- **Alumni Network:** Extend the `core_people` table to support Alumni tracking and networking features.
