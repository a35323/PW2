<?php
require_once __DIR__ . '/../config.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function handle_photo_upload(array $file): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro no upload da fotografia.');
    }
    if ($file['size'] > PHOTO_MAX_BYTES) {
        throw new RuntimeException('Fotografia excede o tamanho máximo.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, PHOTO_ALLOWED_TYPES, true)) {
        throw new RuntimeException('Formato de fotografia inválido. Use JPG ou PNG.');
    }
    $ext = $mime === 'image/png' ? 'png' : 'jpg';
    $name = uniqid('photo_', true) . '.' . $ext;
    $target = PHOTO_UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Falha ao mover fotografia.');
    }
    return 'uploads/photos/' . $name;
}

function select_options(array $options, $selected): string
{
    $html = '';
    foreach ($options as $value => $label) {
        $isSelected = (string)$value === (string)$selected ? 'selected' : '';
        $html .= '<option value="' . e((string)$value) . '" ' . $isSelected . '>' . e((string)$label) . '</option>';
    }
    return $html;
}
?>
