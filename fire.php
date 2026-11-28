<?php
require 'db.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    $res = mysqli_query($link, "SELECT full_name FROM employees WHERE id = $id");
    $row = mysqli_fetch_assoc($res);
    $name = $row ? $row['full_name'] : 'Неизвестно';

    $sql = "UPDATE employees SET is_fired = 1, status = 'fired' WHERE id = $id";
    mysqli_query($link, $sql);
    
    log_action($link, "Уволил сотрудника: $name");
}

header('Location: index.php');
exit;
?>
