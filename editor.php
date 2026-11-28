<?php
require 'connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$asset = [
    'name'             => '',
    'serial_number'    => '',
    'inventory_number' => '',
    'assigned_to'      => '',
    'status'           => 'in_use',
];

if ($id > 0) {
    $sql = "SELECT * FROM assets WHERE id = $id LIMIT 1";
    $r = mysqli_query($link, $sql);
    $asset = mysqli_fetch_assoc($r);

    if (!$asset) {
        die('Нет такого оборудования');
    }
    if ($asset['is_retired']) {
        die('Списанное оборудование нельзя редактировать');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $serial   = trim($_POST['serial_number'] ?? '');
    $inv      = trim($_POST['inventory_number'] ?? '');
    $assigned = trim($_POST['assigned_to'] ?? '');
    $status   = $_POST['status'] ?? 'in_use';

    if ($name === '' || $serial === '' || $inv === '') {
        $error = 'Название, серийный и инвентарный — обязательные';
    } else if (mb_strlen($serial) < 5) {
        $error = 'Серийный номер слишком короткий';
    } else {
        $name_e     = mysqli_real_escape_string($link, $name);
        $serial_e   = mysqli_real_escape_string($link, $serial);
        $inv_e      = mysqli_real_escape_string($link, $inv);
        $assigned_e = mysqli_real_escape_string($link, $assigned);
        $status_e   = mysqli_real_escape_string($link, $status);

        if ($id > 0) {
            $sql = "UPDATE assets SET
                name = '$name_e', serial_number = '$serial_e',
                inventory_number = '$inv_e', assigned_to = '$assigned_e',
                status = '$status_e' WHERE id = $id AND is_retired = 0";
        } else {
            $sql = "INSERT INTO assets (name, serial_number, inventory_number, assigned_to, status)
                VALUES ('$name_e', '$serial_e', '$inv_e', '$assigned_e', '$status_e')";
        }

        if (!mysqli_query($link, $sql)) {
            $error = 'Не сохранилось: ' . mysqli_error($link);
        } else {
            header('Location: dashboard.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error) {
    $asset['name']             = $name;
    $asset['serial_number']    = $serial;
    $asset['inventory_number'] = $inv;
    $asset['assigned_to']      = $assigned;
    $asset['status']           = $status;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= $id ? 'Редактирование' : 'Добавление' ?> оборудования</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Чуть-чуть локальных стилей для формы */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select { max-width: 100%; width: 400px; }
    </style>
</head>
<body>

<div class="container">
    <h1><?= $id ? 'Редактирование' : 'Добавление' ?> оборудования</h1>

    <?php if ($error): ?>
        <p class="error"><?= esc($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Название:</label>
            <input name="name" value="<?= esc($asset['name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Серийный номер:</label>
            <input name="serial_number" value="<?= esc($asset['serial_number']) ?>" required>
        </div>
        <div class="form-group">
            <label>Инвентарный номер:</label>
            <input name="inventory_number" value="<?= esc($asset['inventory_number']) ?>" required>
        </div>
        <div class="form-group">
            <label>Ответственный:</label>
            <input name="assigned_to" value="<?= esc($asset['assigned_to']) ?>">
        </div>
        <div class="form-group">
            <label>Статус:</label>
            <select name="status">
                <option value="in_use"  <?= $asset['status'] === 'in_use'  ? 'selected' : '' ?>>В работе</option>
                <option value="storage" <?= $asset['status'] === 'storage' ? 'selected' : '' ?>>На складе</option>
                <option value="repair"  <?= $asset['status'] === 'repair'  ? 'selected' : '' ?>>В ремонте</option>
            </select>
        </div>
        <button type="submit" class="btn-save">Сохранить</button>
        <a href="dashboard.php" style="margin-left: 15px;">Назад к списку</a>
    </form>
</div>

</body>
</html>
