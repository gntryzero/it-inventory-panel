<?php
require 'connect.php';

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    mysqli_query($link, "UPDATE assets
                         SET is_retired = 1, status = 'retired'
                         WHERE id = $id");
}

header('Location: dashboard.php');
exit;
?>
