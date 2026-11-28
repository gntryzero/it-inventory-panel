<?php
require 'connect.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // Получаем название, чтобы записать в лог
    $res = mysqli_query($link, "SELECT name FROM assets WHERE id = $id");
    $row = mysqli_fetch_assoc($res);
    $name = $row ? $row['name'] : 'Неизвестно';

    $sql = "UPDATE assets SET is_retired = 1, status = 'retired' WHERE id = $id";
    mysqli_query($link, $sql);
    
    // Логируем
    log_action($link, "Списал оборудование: $name");
}

header('Location: dashboard.php');
exit;
?>
