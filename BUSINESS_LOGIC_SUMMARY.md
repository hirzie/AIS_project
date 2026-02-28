# Business Logic Summary

## 1. Finance: Automated Student Billing (Invoicing)
**Logic:**
- **Trigger:** Monthly scheduler or manual "Generate Invoices" action.
- **Process:**
  1.  Identifies **Active Students** for a specific **Academic Year**.
  2.  Fetches **Fee Structure** (`finance_fees`) defined for their **Grade Level**.
  3.  Checks for **Scholarships/Discounts** (`finance_discounts` linked to `student_id`).
  4.  **Invoicing:** Creates a `finance_invoices` record and a corresponding `finance_journal_entry` (Debit: AR, Credit: Revenue).
- **Why:** To prevent manual data entry errors and ensure all students are billed consistently according to their specific level and financial status.

## 2. Academic: Smart Schedule Generator (Shuffle)
**Logic:**
- **Trigger:** "Shuffle Schedule" button in `modules/academic/schedule.php`.
- **Process:**
  1.  **Constraints Check:**
      - **Teacher Availability:** Does the teacher have a "Time Constraint" (e.g., Friday morning off)?
      - **Teacher Conflict:** Is the teacher already teaching in another class at the same time?
      - **Room Conflict:** Is the laboratory/room already occupied?
  2.  **Allocation:** Randomly assigns `subject_id` to available `time_slots` while respecting the `weekly_count` (e.g., 4 hours of Math per week).
  3.  **Backtracking:** If it hits a dead-end (no valid slot left), it retries the generation up to 10 times.
- **Why:** Scheduling is a multi-dimensional constraint problem. This logic automates what would otherwise take days of manual coordination.

## 3. RBAC: Module Access & Overrides
**Logic:**
- **Structure:** `Role` (Static) vs `Allowed Modules` (Dynamic).
- **Process:**
  1.  **Default Permissions:** Each `Role` (e.g., `TEACHER`) has a default set of modules defined in `config/modules.php`.
  2.  **User-Specific Overrides:** The `core_users.access_modules` column (JSON) can contain a custom list of module IDs.
  3.  **Session Hydration:** At login, the system merges defaults + overrides to populate `$_SESSION['allowed_modules']`.
  4.  **Guard Enforcement:** `includes/guard.php` checks this session key before allowing any page render.
- **Why:** Allows fine-grained control (e.g., giving a specific Teacher access to the Finance module for payroll purposes without promoting them to Admin).

## 4. Attendance: Monthly Recalculation & Validation
**Logic:**
- **Trigger:** "Validate Attendance" in `modules/academic/attendance_batch.php`.
- **Process:**
  1.  Aggregates daily attendance marks (`P`, `S`, `I`, `A`) from `acad_attendance_daily`.
  2.  Calculates **Attendance Percentage** for the month.
  3.  **Validation Step:** A Homeroom Teacher must manually "Save" this recap to finalize it.
  4.  Once finalized, the data is locked and becomes visible on the Parent Portal.
- **Why:** Daily data is noisy. Monthly validation provides a "clean" data point for report cards and parent communication.

## 5. Security: Incident & WA Notification
**Logic:**
- **Trigger:** `counseling_incidents.save` API.
- **Process:**
  1.  Saves the incident to `acad_counseling_incidents`.
  2.  **Severity Check:** If `severity` is `HIGH`, it triggers an immediate HTTP request to the WhatsApp Gateway (defined in `core_settings`).
  3.  **Fallback:** If the WA Gateway is down, it logs the failure but continues to save the record to ensure the incident is not lost.
- **Why:** High-severity incidents (e.g., physical injury or major violation) require instant notification for the leadership team.
