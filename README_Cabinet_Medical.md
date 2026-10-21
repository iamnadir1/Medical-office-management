# Cabinet Medical – Medical Office Management System

A web-based application for managing the daily operations of a medical practice, including patients, appointments, consultations, prescriptions, medical certificates,  and printable documents.

This project is a web migration of a medical office management desktop application that I originally built using **Java, Java Swing, and NetBeans**. I then reimplemented and adapted its functionality for the web using **PHP, MySQL, HTML, CSS, and JavaScript**.

## Table of Contents

- [Features](#1-features)
- [Technology Stack](#2-technology-stack)
- [Project Structure](#3-project-structure)
- [How It Works](#4-how-it-works)
- [Database Design](#5-database-design)
- [Installation](#6-installation)
- [Security](#7-security)
- [Java Desktop Version to Web Version](#8-java-desktop-version-to-web-version)
- [Possible Improvements](#9-possible-improvements)

## 1. Features

| Module | Description |
|---|---|
| **Login** | Sign-in, session-based authentication, incorrect-password feedback, and logout |
| **Dashboard** | Overview totals, today's and upcoming appointments, recent consultations, and quick-add actions |
| **Patients** | Add, edit, delete, and search patient records; patient edits are recorded in the modification log |
| **Patient History** | View a patient's details, consultations, appointments, prescriptions, certificates, and modification history |
| **Appointments** | Create, edit, delete, update status (Scheduled / Completed / Cancelled), and filter by status |
| **Consultations** | Record diagnoses, optionally link a consultation to an appointment, and start prescription creation |
| **Medications** | Maintain a medication catalogue with names, dosage details, and forms |
| **Prescriptions** | Select a consultation, add multiple medications with dosage instructions, save, and print |
| **Certificates** | Create medical certificates and work-leave documents with start and end dates, then print them |
| **User Management** | Admin-only account and role management |
| **Printing** | Print-ready prescriptions, medical certificates, work leave, and complete patient histories |

## 2. Technology Stack

### Web application

- **Backend:** PHP 8+ with PDO for database access
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, and vanilla JavaScript
- **Web server:** Apache

### Original desktop application

- **Language:** Java
- **GUI:** Java Swing
- **IDE:** NetBeans

## 3. Project Structure

```text
medical_office_management/
├── index.php              # Dashboard
├── login.php              # Login page
├── logout.php             # Ends the session
├── patients.php           # Patient management
├── patient_history.php    # Full history for one patient
├── appointments.php       # Appointment management
├── consultations.php      # Consultation management
├── prescriptions.php      # Prescription creation and listing
├── certificates.php       # Medical certificates and work leave
├── medicaments.php        # Medication catalogue
├── users.php              # Admin-only user management
├── print.php              # Print-ready documents
├── config/
│   └── database.php       # Database settings and clinic details
├── includes/
│   ├── init.php           # Session, helpers, CSRF, and access control
│   ├── header.php         # Shared sidebar and top bar
│   └── footer.php         # Shared footer and JavaScript loading
├── assets/
│   ├── css/style.css      # Application styling and print styles
│   └── js/app.js          # Form interactions, search, and confirmations
├── database/
│   └── schema.sql         # Database schema
└── sessions/              # PHP session files (created during setup)
```

## 4. How It Works

### 4.1 Request Flow

1. The browser requests a page, such as `patients.php`.
2. Shared initialization code starts the session, checks authentication, and verifies CSRF tokens on form submissions. Unauthenticated users are redirected to the login page.
3. For a `POST` request, the page reads the requested action, runs the corresponding database operation using prepared statements, sets a success or error message, and redirects back to the page.
4. For a `GET` request, the page retrieves data from MySQL and renders the HTML view.
5. JavaScript adds client-side interactions in the browser.

### 4.2 Interface Behaviour

- **Reusable pop-up forms:** Add and edit actions use the same form, with JavaScript filling the fields for editing.
- **Live search and filters:** Search results update by hiding non-matching table rows without a full page reload.
- **Delete confirmation:** Deletion actions ask for confirmation.
- **Dynamic prescription form:** Users can add multiple medication rows.
- **Consultation form:** Selecting an appointment automatically selects its patient.

### 4.3 Roles and Access

| Role | Access |
|---|---|
| `admin` | All modules, including user management |
| `doctor` | All modules except user management |
| `secretary` | All modules except user management |

### 4.4 Typical Workflow

1. Register a patient from **Patients**.
2. Schedule a visit from **Appointments**.
3. Start the consultation from the appointment, record the diagnosis, and save it. The appointment is marked as completed.
4. Create a prescription from the consultation, add medications and dosage instructions, and save or print it.
5. Create a medical certificate or work-leave document and save or print it.
6. Open the patient's history to review the record or print the full history.

### 4.5 Printing

`print.php` creates a print-ready A4-style page containing clinic details, patient information, document content, and a signature line. Users can print the document or save it as a PDF through the browser's print dialog. Navigation and action buttons are hidden in print view.

### 4.6 Modification Log

When patient details are edited, the application compares the old and new values and records changed fields in the `historique` table, including the date, field name, old value, and new value. The log is displayed on the patient's history page.

## 5. Database Design

**Database name:** `cabinet_medical`

| Table | Purpose |
|---|---|
| `utilisateur` | User accounts, password hashes, and roles |
| `patient` | Patient records |
| `rendezvous` | Appointments linked to patients |
| `consultation` | Consultations linked to patients and optionally appointments |
| `ordonnance` | Prescriptions linked to consultations |
| `ordonnance_medicament` | Medications in prescriptions and dosage instructions |
| `medicament` | Medication catalogue |
| `certificat` | Medical certificates and work leave |
| `historique` | Patient modification log |

Deleting a patient also deletes related appointments, consultations, prescriptions, certificates, and log entries through `ON DELETE CASCADE`. Deleting an appointment preserves its consultation while removing the appointment link through `ON DELETE SET NULL`.

## 6. Installation

### 6.1 Requirements

- PHP 8 or newer with the PDO MySQL extension enabled
- MySQL or MariaDB
- Apache or another compatible PHP web server
- A modern web browser

XAMPP or WAMP can provide a local development environment.

### 6.2 Place the Project on the Web Server

1. Copy the project folder into the web server's document directory, such as `htdocs` or `www`.
2. Create a `sessions` folder inside the project.
3. Ensure the web server has permission to write to that folder.

### 6.3 Set Up the Database

For a new installation, import `database/schema.sql` using phpMyAdmin or another MySQL client. The schema creates the `cabinet_medical` database and a default administrator account.

If you are using an existing database, make sure:

- `utilisateur.mot_de_passe` is `VARCHAR(255)` so it can store password hashes.
- `ordonnance_medicament.posologie` exists as `VARCHAR(150)` for dosage instructions.

### 6.4 Configure the Database Connection

Edit `config/database.php` and enter your database settings:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'cabinet_medical');
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', 'YOUR_PASSWORD'); // Your MySQL password
```

The clinic name, address, and phone number used on printed documents are configured in the same file.

### 6.5 Configure Sessions

Ensure the beginning of `includes/init.php` configures the session as follows:

```php
session_name("CABINETSESS");
session_save_path(__DIR__ . "/../sessions");
session_start();
```

This gives the application a dedicated session folder.

### 6.6 Run the Application

1. Start Apache and MySQL.
2. Open the application in your browser, for example:

   `http://localhost/medical_office_management/login.php`

3. For a fresh database installation, use the default account configured by `database/schema.sql`, then change the default password immediately after the first login.

**Important:** Check the SQL file for the actual default credentials before sharing or deploying the project. Do not use default credentials in a production environment.

## 7. Security

The web version includes the following security measures:

- **Prepared statements with PDO** to help prevent SQL injection.
- **CSRF tokens** on form submissions.
- **Escaped output** to help prevent cross-site scripting (XSS).
- **Password hashing** with PHP's `password_hash()` function.
- **Session ID regeneration** at login.
- **Role-based restriction** of the user management page to administrators.

## 8. Java Desktop Version to Web Version

The web application was developed from a desktop project that I originally built using **Java Swing in NetBeans**. During the migration, I adapted the application's workflows and functionality to a browser-based PHP application.

| Original Java desktop application | PHP web application |
|---|---|
| Java Swing windows and forms | PHP pages with HTML, CSS, and JavaScript |
| `Main`, `LoginFrame`, `Utilisateur` | `login.php`, `index.php`, `users.php` |
| `DatabaseConnection` | `config/database.php` |
| `PatientDAO` | `patients.php` |
| `PatientHistoryDAO` | `patient_history.php` |
| `RendezVousDAO` | `appointments.php` |
| `ConsultationDAO` | `consultations.php` |
| `MedicamentDAO` | `medicaments.php` |
| `PrintUtils` | `print.php` |
| Prescription and certificate functionality | `prescriptions.php`, `certificates.php` |

This project demonstrates experience with both desktop and web application development, relational database integration, and adapting an existing application's functionality to a different technology stack.

## 9. Possible Improvements

- Editing existing prescriptions and certificates
- More granular permissions for doctors and secretaries
- Calendar view for appointments
- Dashboard statistics and charts
- Password reset functionality
- Pagination for long lists

## Author

**[Halladj Mohamed Nadir]**


