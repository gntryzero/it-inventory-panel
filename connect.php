<?php
// подключение. root без пароля потому что локалка
$link = mysqli_connect('localhost', 'root', '', 'equipment_db');

if (!$link) {
    die('Не подключилось: ' . mysqli_connect_error());
}

// чтобы русские буквы нормально шли
mysqli_set_charset($link, 'utf8mb4');

// экранирование для html (защита от xss)
function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// функция для записи действий в лог
// подсмотрел на хабре
function log_action($link, $text) {
    $text_e = mysqli_real_escape_string($link, $text);
    mysqli_query($link, "INSERT INTO action_logs (action) VALUES ('$text_e')");
}
?>
