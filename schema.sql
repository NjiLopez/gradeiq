CREATE DATABASE IF NOT EXISTS gradeiq
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gradeiq;

CREATE TABLE IF NOT EXISTS users (
    id          VARCHAR(12)     NOT NULL PRIMARY KEY,
    username    VARCHAR(60)     NOT NULL UNIQUE,
    full_name   VARCHAR(120)    NOT NULL,
    password    VARCHAR(255)    NOT NULL,
    role        ENUM('admin','student') NOT NULL DEFAULT 'student',
    student_id  VARCHAR(60)     NOT NULL DEFAULT '',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exams (
    id              VARCHAR(12)     NOT NULL PRIMARY KEY,
    title           VARCHAR(200)    NOT NULL,
    subject         VARCHAR(100)    NOT NULL DEFAULT '',
    description     TEXT,
    q_count         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    answer_key      TEXT            NOT NULL,   -- JSON text for {"1":"A","2":"C",...}
    questions       TEXT            NULL,       -- JSON text for {"1":"Question text",...}
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- 0 = no timer
    pass_threshold  TINYINT UNSIGNED NOT NULL DEFAULT 50,
    created_by      VARCHAR(12)     NOT NULL DEFAULT '',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id              VARCHAR(12)     NOT NULL PRIMARY KEY,
    exam_id         VARCHAR(12)     NOT NULL,
    user_id         VARCHAR(12)     NOT NULL DEFAULT '',
    student_name    VARCHAR(120)    NOT NULL,
    student_id_ref  VARCHAR(60)     NOT NULL DEFAULT '',
    answers         TEXT            NOT NULL,   -- JSON text for {"1":"B","2":"A",...}
    result          TEXT            NOT NULL,   -- JSON text for full grading result object
    graded_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_exam_id   (exam_id),
    INDEX idx_user_id   (user_id),
    INDEX idx_graded_at (graded_at),
    CONSTRAINT fk_session_exam FOREIGN KEY (exam_id)
        REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;