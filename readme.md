## Login Flow - Step-by-Step

1. **User Opens Login Page**
   - `views/login.php` is rendered.
   - Login form contains fields: Email, Password, CAPTCHA.

2. **User Submits Login Form (AJAX Request)**
   - Form data sent to `controllers/AuthController.php` via POST.
   - Fields submitted:
     - email
     - password
     - captcha_code
     - loginsubmit = true

3. **Session Starts**
   - `session_start()` initializes session.

4. **CAPTCHA Validation**
   - Check if `$_SESSION['captcha_code']` exists.
   - Match submitted `captcha_code` (case-insensitive).
   - ❌ If invalid → return `"Invalid Captcha Code."` and exit.

5. **Email & Password Sanitization**
   - Email is sanitized using `mysqli_real_escape_string`.
   - Password is hashed using `md5()`.

6. **User Lookup in Database**
   - Query `slotbooking.login` table for user by email.
   - ❌ If user not found → return `"Invalid Email."` and exit.

7. **Password Check**
   - Compare MD5 hash of submitted password with DB record.
   - ❌ If mismatch → return `"Invalid Password."` and exit.

8. **Account Expiry Check**
   - Compare `expiry_date` from DB with current date.
   - ❌ If expired → return `"Your account has expired."` and exit.

9. **Session Initialization**
   - Set:
     - `$_SESSION['memberid']`
     - `$_SESSION['login']`
     - `$_SESSION['role']` (1 if `memberid == 1`, else 0)

10. **Check IT Admin Role**
    - Query `slotbooking.role_permissions` for role = 1.
    - If `memberid` has role = 1 → set `$_SESSION['role_ITadmin'] = 1`.

11. **Success Response**
    - Return `"ok"` to AJAX request.
    - JS then redirects to `views/complaint.php`.

12. **Failure Response**
    - Any failure returns an error message.
    - Message shown via `#errorMsg` div in `login.php`.

## Additional Notes:
- DB connection is established from `config/connect.php` (multi-db setup).
- CAPTCHA is served from `public/captcha.php`.
- Slotbooking DB is used for authentication.


## Sidebar Menu - Equipment Troubleshooting Module

1. **Session Check**
   - `session_start()` ensures session is active.
   - Displays: `Logged in as [User's Name]` using `getName(memberid)`.

2. **Menu Items**

- 🔹 **Complaint Form**
  - → `views/complaint.php` — Create new complaint.

- 🔹 **My Complaints** *(Expandable)*
  - → `views/my_complaint.php?type=1~4`
  - Types: Equipment (1), Facility (2), Safety (3), Process (4)

- 🔹 **Pending Complaints** *(Expandable)*
  - → `views/all_complaints.php?type=X&status=pending&importance=all`

- 🔹 **Closed Complaints** *(Expandable)*
  - → `views/all_complaints.php?type=X&status=closed&importance=all`

- 🔹 **Periodic Checks**
  - → `views/periodic_checks.php` — View/upload checklist XLSX files.

- 🔹 **Tool Down Duration** *(Expandable)*
  - → `views/tool_down_duration.php?type=1~3`
  - Types: Equipment (1), Facility (2), Safety (3)

- 🔹 **Statistics** *(Expandable)*
  - → `views/statistics.php` (dashboard)
  - *(Other type-wise stats links are commented)*

- 🔹 **Help**
  - → Opens PDF: `assets/efp_troubleshooting_help.pdf`

- 🔹 **Permission**
  - → `views/permission.php`
  - Visible **only if**: `role == 1 || role_ITadmin == 1`

- 🔹 **Logout**
  - → `logout.php` — Destroys session, redirects to login.

3. **Responsive Design**
   - On mobile, menu collapses with a toggle button (`#sideMenu`).
   - Bootstrap’s collapse is used for all expandable sections.

4. **Dynamic Content**
   - Menu categories (`$types`) auto-generated from PHP loop.



---

### 🔁 **Complaint Submission Flow (`complaint.php`)**

#### 🧠 1. **Auth + Setup**

* Includes session check, header, DB config, and common functions.
* Prepares for POST handling & form rendering.

---

#### 📝 2. **On Form Submission**

* **Reads input:**

  * `type`, `tools_name`, `description`
  * Optional: `process_develop`, `anti_contamination_develop` (only for "Process" type)
* **Inserts Complaint:**

  * Calls `insert_complaint()` with relevant data.
* **Handles file upload (optional):**

  * If file present, gets latest complaint via `complaint()`.
  * Saves file as `uploads/{complaint_id}.ext`.
  * Stores file path with `upload_file_complaint()`.

---

#### 🖼️ 3. **Frontend Form**

* **Complaint Type (radio)** – Equipment / Facility / Safety / Process
  ⤷ On select, triggers corresponding `loading_tools_*()` JS function.

* **Tool Dropdown** (dynamic)
  ⤷ Populated via PHP inside JS functions, based on type.

* **Conditional Fields**

  * Only shown for "Process":

    * `Process Development`
    * `Anti-Contamination Development`

* **Description** – Required textarea

* **File Upload** – Optional, with `.exe` restriction

---

#### ⚙️ 4. **JavaScript Logic**

* Enables form fields only after type is selected.
* Validates:

  * Tool is selected
  * Description is filled
  * `.exe` file upload is blocked
* Each `loading_tools_*()` function populates the tool dropdown from `getTools(type)`.

---

#### ✅ 5. **UI/UX Notes**

* All form fields are disabled initially.
* Dynamically enabled post type selection.
* Shows success message on successful submission.
* Error messages shown below the red `*` note.

---


---

### 🧭 **Page Flow: My Complaints (`my_complaint.php`)**

---

#### 🔐 **1. Access Control & Setup**

* Includes:

  * `auth_check.php` – ensures user is logged in.
  * `header.php`, `connect.php`, `common.php` – base layout & helper functions.
* `$_GET['type']` – used to filter complaint type (1–4).

---

#### 📦 **2. Data Fetch**

* Calls:

  * `complaint($_SESSION['memberid'], $type, '')` – fetches current user’s complaints of selected type.
* Complaint types:

  * `1 = Equipment`, `2 = Facility`, `3 = Safety`, `4 = Process`

---

#### 🧾 **3. Page Layout**

* **Sidebar Menu** – loaded from `menu.php`.
* **Main Card** – shows "My Complaints - \[Type]".

---

#### 📊 **4. Complaint Table (if any)**

Each row shows:

* **Member Name** + **Timestamp**
* **Tool Name** (resolved via tool-name functions based on type)
* **Short Complaint Description** (max 100 words, cleans line breaks)
* **Process/Anti Contamination** (only for `type == 4`)
* **Status**:

  * Pending / In Process / Closed / On Hold
  * If **Closed**, shows resolved date & total days taken
* **Tracking Link**:

  * If tracking exists, opens `view_tracks.php` in modal
* **Expected Completion Date**:

  * Fetched using `EC_date()`

---

#### 🧩 **5. Other Logic**

* Modal dialog (`#dialog`) for **View Tracking**
* Uses **jQuery UI** dialog + AJAX `load()` call
* **DataTable** applied to make complaints table sortable/paginated

---

#### 🧹 **6. No Complaints Case**

* Shows: “No complaints found for this category.”

---


## `my_complaint.php`

This page displays a logged-in user's complaints filtered by type (Equipment, Facility, Safety, or Process). It supports viewing complaint details, tracking history, and expected resolution timelines.

---

### 🔧 Dependencies

* `auth_check.php` – Session and auth validation
* `header.php`, `footer.php`, `menu.php` – Common layout includes
* `connect.php` – DB connection
* `common.php` – Utility functions (`complaint()`, `getToolName()`, etc.)

---

### 📥 GET Parameter

* `type` (int) — Category of complaints:

  * `1`: Equipment
  * `2`: Facility
  * `3`: Safety
  * `4`: Process

---

### ⚙️ Functions Used

* `complaint($memberId, $type, '')`
* `getToolName()` / `getToolName_facility()` / `getToolName_safety()`
* `getName($memberId)`
* `display_timestamp($ts)`
* `display_date($date)`
* `count_day($from, $to)`
* `EC_date($complaint_id)`
* `trouble_track($complaint_id, '')`

---

### 📊 Output

* Bootstrap card with:

  * Complaint table filtered by type
  * Status display: Pending, In process, Closed (with resolved date & duration), On Hold
  * Links to uploaded files (if any)
  * Tracking view via jQuery UI modal
  * Expected completion date

---

### 🧠 Conditional Logic

* **Dynamic columns**:

  * If `type == 4`, show extra fields:

    * `Process Development`
    * `Anti Contamination Development`
* **Tool Name Resolution** depends on `type`
* **Status Mapping** uses integer → label conversion

---

### 🖱️ JS Behavior

* Initializes DataTable on complaints list
* Modal dialog opens `view_tracks.php?complaint_id=X&type=Y` in a jQuery UI dialog on "View Tracking" click

---

### ⚠️ Notes

* Skips rendering complaints that don't match the `type`
* Limits description to 100 words with line break handling and HTML escaping
* File links open in a new tab

---




### 🔁 **Flow: Periodic Check Upload Page**

---

#### 🔐 **1. Setup & Access**

* Includes:

  * Auth check, header, DB config, common functions
* Only logged-in users can access (`$_SESSION['login']`)
* Types of periodic checks handled:

  * `ahu`, `chiller`, `eblower`, `earthpit`, `ups`

---

#### 📂 **2. File Upload Logic**

* Function: `**handleFileUpload**($field)`

  * Checks if file is selected
  * Ensures file extension is `.xlsx`
  * Moves file to `../periodic_check/{field}.xlsx`
  * Sets success or error message
* Trigger:

  * Detects which **submit button** was clicked from `$_POST['Submit_{field}']`
  * Calls upload function for that field

---

#### 🧾 **3. Page UI Layout**

* **Left Column**: `menu.php` sidebar
* **Right Column**: Card with "Periodic Checks" heading

---

#### 📄 **4. File Upload Table**

Each row (AHU, Chiller, etc.) shows:

* **No.**
* **Type** (e.g., "AHU")
* **Current File** (download link if exists)
* **Upload Input** (visible only if user has `facility` permission)
* **Submit Button** (per field)

---

#### ✅ **5. Messages**

* Displays:

  * Success (`$msg`) in green
  * Error (`$msg1`) in red

---

#### 🧩 **6. Permissions**

* Only users with `facility` permission (via `check_permission('facility', $_SESSION['memberid'])`) can:

  * Upload new files
  * See file input and submit buttons

---


Here’s a concise but clear `README.md` for your `permission.php` page:

---

# Permission Management Page

## Overview

This page provides an **administrative interface** to manage staff permissions within the system.
It allows **assigning**, **updating**, and **removing** permissions for staff members across four permission types:

* **Equipment**
* **Facility**
* **Safety**
* **Process**

Only **IT Admins** (`role_ITadmin`) or users with **role = 1** can access this page.

---

## Features

### 1. Assign New Permission

* Select a staff member **without existing permissions**.
* Choose one permission type.
* Assign instantly.

### 2. Edit Existing Permissions

* View all staff with permissions.
* Toggle each of the four permission types individually.
* Update all changes at once.

### 3. Delete Permission Entry

* Permanently remove a permission record for a user.

---

## Security & Access

* Requires active session and **admin-level role**.
* Redirects unauthorized users to `logout.php`.

---

## Data Sources

* **`permission_details()`**: Retrieves existing permission records.
* **`staff_list()`**: Fetches all staff members.
* **`expired_memberid()`**: Returns IDs of expired staff accounts (highlighted in yellow).
* **`user_permission_exit($sid)`**: Checks if a staff member already has a permission record.

---

## Database Actions

* **Assign**:
  `add_permission(equipment, facility, safety, process, uid)`
* **Update**:
  `update_permission(equipment, facility, safety, process, uid)`
* **Delete**:
  `DELETE FROM permission WHERE id = ?`

---

## UI Highlights

* Staff with expired accounts are highlighted **yellow** (`#FF9`).
* Other staff are shown with a **light grey background**.
* Permissions are managed via **checkboxes**.
* Inline **JavaScript confirm** prompt before deletion.

---

## File Dependencies

* `../includes/auth_check.php` – Session & access validation.
* `../includes/header.php` – HTML header template.
* `../config/connect.php` – DB connection.
* `../includes/common.php` – Shared functions.
* `../includes/menu.php` – Sidebar menu.
* `../includes/footer.php` – Page footer.

---

## Usage

1. Log in as **IT Admin** or a **role=1** user.
2. Navigate to `permission.php`.
3. Assign, update, or delete permissions as required.

---

## Notes

* Bulk updates overwrite all permissions for each listed user.
* Permissions are stored as binary values (`1` for active, `0` for inactive).
* Uses Bootstrap classes for responsive layout.

---
