<?php
// подключение к базе сотрудников
$link = mysqli_connect('localhost', 'root', '', 'staff_db');

if (!$link) {
    die('Не подключилось: ' . mysqli_connect_error());
}

mysqli_set_charset($link, 'utf8mb4');

// экранирование для html
function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// функция для логов
function log_action($link, $text) {
    $text_e = mysqli_real_escape_string($link, $text);
    mysqli_query($link, "INSERT INTO action_logs (action) VALUES ('$text_e')");
}
?>
