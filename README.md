# Digital Document Repository Platform

A full-stack web application built for **BCA 5th Semester – Web Application Development using PHP**.
Users can register, log in, upload documents (PDF, DOCX, PPT, JPG, PNG), organize them into
categories, search/download/preview/edit them, and manage their profile. Admins get a
separate panel to manage users, documents, categories and view upload reports/analytics.

---

## 🛠 Technology Stack

| Layer      | Technology                          |
|------------|--------------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5, JavaScript |
| Backend    | Core PHP (no framework)              |
| Database   | MySQL (via MySQLi, Prepared Statements) |
| Server     | Apache (XAMPP)                       |

---

## 📁 Folder Structure

```
Digital-Document-Repository/
│
├── admin/                     # Admin panel
│   ├── includes/               # admin-header.php, admin-footer.php
│   ├── index.php               # Admin dashboard & analytics
│   ├── manage-users.php        # Manage/Block/Delete users
│   ├── manage-documents.php    # Manage all documents
│   ├── manage-categories.php   # Manage categories
│   └── reports.php             # Upload reports
│
├── user/
│   ├── categories.php          # User category management
│   └── search.php              # Advanced search
│
├── uploads/
│   ├── documents/              # Uploaded documents stored here
│   └── profile_pics/           # Profile pictures stored here
│
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/default-avatar.png
│
├── config/
│   ├── database.php            # DB connection (MySQLi)
│   └── config.php              # Global constants + session start
│
├── includes/
│   ├── header.php              # Shared navbar/header (user side)
│   ├── footer.php
│   └── functions.php           # Helper functions
│
├── index.php                   # Entry point (redirects based on session)
├── login.php
├── register.php
├── forgot-password.php
├── reset-password.php
├── logout.php
├── dashboard.php                # User dashboard
├── upload.php                   # Upload document
├── documents.php                # List/search/preview/delete documents
├── edit-document.php            # Edit document details / new version
├── download.php                 # Secure file download
├── profile.php                  # Edit profile / change password / picture
└── database.sql                 # Complete database schema + seed data
```

---

## 🚀 Step-by-Step XAMPP Installation Guide

1. **Install XAMPP**
   - Download XAMPP from https://www.apachefriends.org
   - Install it (includes Apache + MySQL + PHP + phpMyAdmin).

2. **Copy the project**
   - Copy the entire `Digital-Document-Repository` folder into your XAMPP `htdocs` directory:
     - Windows: `C:\xampp\htdocs\Digital-Document-Repository`
     - macOS/Linux: `/Applications/XAMPP/htdocs/Digital-Document-Repository` (or `/opt/lampp/htdocs/...`)

3. **Start Apache and MySQL**
   - Open the XAMPP Control Panel.
   - Click **Start** next to both **Apache** and **MySQL**.

4. **Create the database**
   - Open your browser and go to `http://localhost/phpmyadmin`
   - Click **Import** (or **SQL** tab and paste the file contents).
   - Select the file `database.sql` from the project folder and click **Go**.
   - This will automatically create the `digital_document_repository` database with all
     tables, keys, constraints, a default admin account and sample categories.

5. **Configure the database connection**
   - Open `config/database.php`.
   - By default it uses:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'digital_document_repository');
     ```
   - This matches the default XAMPP MySQL setup (no password for `root`). Change if your
     setup differs.

6. **Set the Base URL**
   - Open `config/config.php` and confirm:
     ```php
     define('BASE_URL', '/Digital-Document-Repository/');
     ```
   - This must match the folder name you placed inside `htdocs`.

7. **Set folder permissions (Linux/macOS only)**
   ```bash
   chmod -R 755 uploads/
   ```

8. **Run the project**
   - Open your browser and visit:
     `http://localhost/Digital-Document-Repository/`
   - You will be redirected to the login page.

---

## 🔐 Two Separate Login Pages

This platform uses **two distinct login pages** so each side of the system is only reachable
through its own door:

| Login Page | URL | Who can log in |
|------------|-----|-----------------|
| **User Login** | `http://localhost/ggy/login.php` | Regular (`role='user'`) accounts only. Admin accounts are rejected here with a message pointing to the Admin Login. |
| **Admin Login** | `http://localhost/ggy/admin/login.php` | Admin (`role='admin'`) accounts only. Regular user accounts are rejected here with a message pointing to the User Login. |

Each login page checks the account's `role` column and refuses to authenticate an account
that doesn't belong on that page — so a regular user can never reach the admin panel by
guessing a URL, and an admin account can't accidentally end up on the plain user dashboard
from the login form. Logging out also sends you back to whichever login page matches the
role you just logged out of.

## 🔐 Default Login Credentials

| Role  | Login Page | Username / Email       | Password   |
|-------|------------|-------------------------|------------|
| Admin | `admin/login.php` | `admin` / admin@ddrp.com | `Admin@123` |
| User  | `login.php` | Register a new account via `register.php` | — |

---

## ✅ Key Features Implemented

### User Side
- Registration, Login, Forgot/Reset Password, Logout, Session Management
- Dashboard: total documents, recent uploads, categories, storage stats
- Upload / Download / Delete / Edit / Preview documents (PDF, DOCX, PPT, JPG, PNG)
- Document version history (re-upload creates a new version, tracked in `uploads` table)
- Category CRUD + assign category while uploading
- Search by document name, category, and upload date
- Profile editing, password change, profile picture upload

### Admin Side
- Manage Users (view, block/unblock, delete)
- Manage Documents (view/download/delete any user's document)
- Manage Categories (create/edit/delete)
- Upload Reports with date-range filter and print option
- Dashboard analytics (totals, category breakdown, top contributors, recent activity log)

---

## 🔒 Security Measures

- All SQL queries use **Prepared Statements** (MySQLi) to prevent SQL Injection.
- Passwords hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- User input sanitized with `htmlspecialchars()` to prevent XSS.
- File uploads validated by extension and size; PHP execution disabled inside `uploads/`
  via `.htaccess`.
- Session-based authentication with role-based access control (`requireLogin()`, `requireAdmin()`).
- Users can only view/edit/delete their own documents; admins have elevated access.

---

## 🖼 Screenshots & Diagrams

This deliverable was generated in a sandboxed environment without a live XAMPP/MySQL
server, so browser screenshots of each running page could not be captured here. Once you
follow the installation steps above, every page (login, register, dashboard, upload,
documents, categories, search, profile, and the full admin panel) will render using the
Bootstrap 5 UI described in this README — take screenshots directly from your browser for
your submission.

The `docs/` folder contains ready-to-use diagrams for your report:
- `er_diagram.png` — Entity-Relationship Diagram
- `dfd_level0.png` / `dfd_level1.png` — Data Flow Diagrams
- `flowchart.png` — System flowchart
- `Project_Report.docx` — Complete project report (Introduction, Objectives, Scope,
  Software/Hardware Requirements, ER Diagram, Database Schema, DFD, Flowchart, Modules,
  Testing, Future Scope, Conclusion)

## 📌 Notes

- The "Forgot Password" flow shows the reset link directly on screen (instead of emailing it)
  because a live SMTP server isn't available in a local XAMPP demo environment. In a real
  deployment, integrate PHPMailer/SMTP to email the link instead.
- Maximum upload size is 10 MB per file (`config/config.php` → `MAX_FILE_SIZE`), adjustable.
- Allowed file types: PDF, DOC, DOCX, PPT, PPTX, JPG, JPEG, PNG.

---

## 👨‍🎓 Project Info

**Subject:** Web Application Development using PHP
**Course:** BCA – 5th Semester
**Project Title:** Digital Document Repository Platform
