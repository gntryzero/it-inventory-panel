<?php
$link = mysqli_connect('localhost', 'root', '', 'equipment_db');
if (!$link) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}
mysqli_set_charset($link, 'utf8mb4');

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
