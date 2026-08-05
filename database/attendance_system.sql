-- ============================================================
-- Attendance Management System — Clean Production Schema
-- Database: attendance_system
-- Role System: Admin, Head Teacher, Student
-- ============================================================

CREATE DATABASE IF NOT EXISTS `attendance_system`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `attendance_system`;

-- --------------------------------------------------------
-- Table: users
-- Stores authentication credentials for all roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)      NOT NULL,
  `password`   VARCHAR(255)     NOT NULL COMMENT 'bcrypt hashed',
  `role`       ENUM('admin','head_teacher','student') NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: teachers
-- Extended profile for head teacher users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teachers` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `name`           VARCHAR(100) NOT NULL,
  `email`          VARCHAR(100) NOT NULL,
  `department`     VARCHAR(50)  DEFAULT NULL,
  `assigned_class` VARCHAR(50)  DEFAULT NULL COMMENT 'Homeroom class assigned',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teacher_email` (`email`),
  UNIQUE KEY `uq_teacher_user` (`user_id`),
  CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: students
-- Student profile; linked to a login user & head teacher
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`             INT UNSIGNED DEFAULT NULL COMMENT 'linked login account',
  `name`                VARCHAR(100) NOT NULL,
  `email`               VARCHAR(100) NOT NULL,
  `class`               VARCHAR(50)  NOT NULL,
  `roll_number`         VARCHAR(30)  DEFAULT NULL,
  `assigned_teacher_id` INT UNSIGNED DEFAULT NULL,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_email` (`email`),
  UNIQUE KEY `uq_roll_number`   (`roll_number`),
  CONSTRAINT `fk_student_user`    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_student_teacher` FOREIGN KEY (`assigned_teacher_id`)
    REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: attendance
-- Morning attendance record per student per date
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `date`       DATE         NOT NULL,
  `status`     ENUM('Present','Absent','Late') NOT NULL DEFAULT 'Absent',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance` (`student_id`, `date`),
  CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_teacher` FOREIGN KEY (`teacher_id`)
    REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- DEFAULT SUPER ADMIN ACCOUNT
-- Username: admin
-- Passcode: password123
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$12$eImiTXuWVxfM37uY4JANjQ.H4e9TXmlUAlc4H5EXcKWFbFBqQiLIu', 'admin');
