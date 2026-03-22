<?php
// Global configuration
const DB_HOST = '127.0.0.1';
const DB_NAME = 'pw2';
const DB_USER = 'root';
const DB_PASS = '';
const SESSION_TIMEOUT_MINUTES = 30;
const APP_NAME = 'Gestão Académica Interna';

// File upload constraints
const PHOTO_UPLOAD_DIR = __DIR__ . '/uploads/photos';
const PHOTO_MAX_BYTES = 2 * 1024 * 1024; // 2MB
const PHOTO_ALLOWED_TYPES = ['image/jpeg', 'image/png'];

// Start session and output buffer early
if (session_status() === PHP_SESSION_NONE) {
    // Single session bootstrap for the whole app lifecycle.
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

// Session timeout handling
if (isset($_SESSION['last_seen']) && (time() - $_SESSION['last_seen']) > SESSION_TIMEOUT_MINUTES * 60) {
    // Expire inactive sessions to reduce risk from abandoned logins.
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_seen'] = time();

// Roles
const ROLE_STUDENT = 'aluno';
const ROLE_STAFF = 'func';
const ROLE_MANAGER = 'gestor';

// Status enums (PT)
const PROFILE_STATUSES = ['rascunho', 'submetida', 'aprovada', 'rejeitada'];
const ENROLL_STATUSES = ['pendente', 'aprovado', 'rejeitado'];
const SHEET_SEASONS = ['Normal', 'Recurso', 'Especial'];
?>
