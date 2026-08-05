# Attendance Management System

A modern, role-based school attendance management system built with PHP and MySQL. Features a **Nothing OS-inspired** dark UI design with three distinct user roles: Super Admin, Head Teacher, and Student.

---

## Tech Stack

- **Backend**: PHP 7.4+ (PDO, bcrypt)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Design**: Nothing OS-inspired (OLED black, dot-matrix typography, crimson red accent)
- **Server**: XAMPP / WAMP / any Apache + PHP + MySQL stack

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP, WAMP, or any local server with Apache + PHP + MySQL

## Installation

1. Clone or download this project into your server's web root:
   ```
   C:\xampp\htdocs\Attendance-Management-System-master\
   ```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Run the database installer in your browser:
   ```
   http://localhost/Attendance-Management-System-master/setup_db.php
   ```
   This will automatically create the `attendance_system` database, all tables, and a default Super Admin account.

4. Open the login page:
   ```
   http://localhost/Attendance-Management-System-master/index.php
   ```

## Default Admin Credentials

| Role        | Username | Password       |
|-------------|----------|----------------|
| Super Admin | `admin`  | `password123`  |

> Change the default password after first login via **Admin → Profile**.

## User Roles

### 🛡️ Super Admin (`/admin`)
- View school-wide attendance dashboard
- Add, edit, and delete Head Teachers
- Assign homeroom classes to teachers
- View all students across the school
- Reassign students between classes
- Generate school-wide attendance reports

### 🎓 Head Teacher (`/head_teacher`)
- Manage students in their assigned homeroom class (add / edit / remove)
- Mark 1st period morning attendance for the class
- View and edit class attendance history
- Monitor low-attendance students

### 📚 Student (`/student`)
- View personal attendance dashboard with percentage gauge
- Track attendance streak
- Browse personal date-wise attendance log

## Getting Started

1. **Log in as Super Admin** → Go to **Head Teachers** → Add your teachers and assign their homeroom class (e.g. `Class 10-A`).
2. **Log in as Head Teacher** → Go to **Students** → Add students to your class.
3. **Mark Attendance** → Go to **Mark Attendance** → Record the morning 1st period attendance.
4. **Students can log in** to view their own attendance records and stats.

## Project Structure

```
├── index.php                  # Login page (3-role selector)
├── logout.php                 # Session logout
├── setup_db.php               # Database installer
│
├── admin/                     # Super Admin portal
│   ├── dashboard.php
│   ├── teachers.php
│   ├── students.php
│   ├── reports.php
│   └── profile.php
│
├── head_teacher/              # Head Teacher portal
│   ├── dashboard.php
│   ├── students.php
│   ├── attendance.php
│   ├── view_attendance.php
│   └── profile.php
│
├── student/                   # Student portal
│   ├── dashboard.php
│   ├── attendance.php
│   └── profile.php
│
├── config/
│   └── db.php                 # PDO database connection
│
├── database/
│   └── attendance_system.sql  # Database schema
│
├── includes/
│   ├── auth.php               # Session guards & role helpers
│   ├── flash.php              # Toast notification system
│   ├── sidebar_admin.php      # Admin layout template
│   ├── sidebar_teacher.php    # Teacher layout template
│   ├── sidebar_student.php    # Student layout template
│   └── page_footer.php        # Footer template
│
└── assets/
    ├── css/
    │   └── style.css          # Nothing OS design system
    └── js/
        └── app.js             # UI interactions & animations
```

## Database Tables

| Table        | Description                                      |
|--------------|--------------------------------------------------|
| `users`      | Login credentials for all roles (bcrypt hashed)  |
| `teachers`   | Head Teacher profiles & homeroom class assignment|
| `students`   | Student profiles linked to a teacher & class     |
| `attendance` | Daily attendance records (Present / Absent / Late)|

## Security

- All passwords hashed with **bcrypt** (cost factor 12)
- **PDO prepared statements** for all database queries (SQL injection prevention)
- Session-based authentication with role guards
- CSRF-safe form submissions via POST

## License

This project is open source and available for educational purposes.
