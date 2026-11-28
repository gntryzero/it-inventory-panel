<?php
require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$emp = [
    'full_name'       => '',
    'passport_number' => '',
    'phone_number'    => '',
    'position'        => '',
    'status'          => 'active',
];

if ($id > 0) {
    $sql = "SELECT * FROM employees WHERE id = $id LIMIT 1";
    $r = mysqli_query($link, $sql);
    $emp = mysqli_fetch_assoc($r);

    if (!$emp) die('Нет такого сотрудника');
    if ($emp['is_fired']) die('Уволенного нельзя редактировать');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = trim($_POST['full_name'] ?? '');
    $passport = trim($_POST['passport_number'] ?? '');
    $phone    = trim($_POST['phone_number'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $status   = $_POST['status'] ?? 'active';

    if ($name === '' || $passport === '' || $phone === '') {
        $error = 'ФИО, паспорт и телефон — обязательные поля';
    } else {
        $name_e     = mysqli_real_escape_string($link, $name);
        $passport_e = mysqli_real_escape_string($link, $passport);
        $phone_e    = mysqli_real_escape_string($link, $phone);
        $position_e = mysqli_real_escape_string($link, $position);
        $status_e   = mysqli_real_escape_string($link, $status);

        if ($id > 0) {
            $sql = "UPDATE employees SET
                full_name = '$name_e', passport_number = '$passport_e',
                phone_number = '$phone_e', position = '$position_e',
                status = '$status_e' WHERE id = $id AND is_fired = 0";
            $log_msg = "Отредактировал сотрудника: $name";
        } else {
            $sql = "INSERT INTO employees (full_name, passport_number, phone_number, position, status)
                VALUES ('$name_e', '$passport_e', '$phone_e', '$position_e', '$status_e')";
            $log_msg = "Добавил нового сотрудника: $name";
        }

        if (!mysqli_query($link, $sql)) {
            $error = 'Не сохранилось: ' . mysqli_error($link);
        } else {
            log_action($link, $log_msg);
            header('Location: index.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error) {
    $emp['full_name']       = $name;
    $emp['passport_number'] = $passport;
    $emp['phone_number']    = $phone;
    $emp['position']        = $position;
    $emp['status']          = $status;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= $id ? 'Редактирование' : 'Добавление' ?> сотрудника</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select { max-width: 100%; width: 400px; }
    </style>
</head>
<body>

<div class="container">
    <h1><?= $id ? 'Редактирование' : 'Добавление' ?> сотрудника</h1>

    <?php if ($error): ?>
        <p class="error"><?= esc($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>ФИО:</label>
            <input name="full_name" value="<?= esc($emp['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Паспорт (4 цифры, пробел, 6 цифр):</label>
            <input name="passport_number" id="passport" value="<?= esc($emp['passport_number']) ?>" placeholder="1234 567890" required>
        </div>
        <div class="form-group">
            <label>Телефон:</label>
            <input name="phone_number" id="phone" value="<?= esc($emp['phone_number']) ?>" placeholder="+7 999 123-45-67" required>
        </div>
        <div class="form-group">
            <label>Должность:</label>
            <input name="position" value="<?= esc($emp['position']) ?>">
        </div>
        <div class="form-group">
            <label>Статус:</label>
            <select name="status">
                <option value="active"  <?= $emp['status'] === 'active'  ? 'selected' : '' ?>>Работает</option>
                <option value="vacation" <?= $emp['status'] === 'vacation' ? 'selected' : '' ?>>В отпуске</option>
                <option value="sick" <?= $emp['status'] === 'sick' ? 'selected' : '' ?>>На больничном</option>
            </select>
        </div>
        <button type="submit" class="btn-save">Сохранить</button>
        <a href="index.php" style="margin-left: 15px;">Назад к списку</a>
    </form>
</div>

<!-- МАСКИ ВВОДА -->
<script>
// Маска для паспорта: 1234 567890
document.getElementById('passport').addEventListener('input', function (e) {
    let val = this.value.replace(/\D/g, ''); // только цифры
    if (val.length > 4) {
        this.value = val.slice(0, 4) + ' ' + val.slice(4, 10);
    } else {
        this.value = val;
    }
});

// Маска для телефона: +7 (999) 123-45-67
document.getElementById('phone').addEventListener('input', function (e) {
    let val = this.value.replace(/\D/g, '');
    if (val.length === 0) { this.value = ''; return; }
    if (val[0] === '7' || val[0] === '8') {
        val = val.substring(1);
    }
    let res = '+7 ';
    if (val.length > 0) res += '(' + val.substring(0, 3);
    if (val.length >= 4) res += ') ' + val.substring(3, 6);
    if (val.length >= 7) res += '-' + val.substring(6, 8);
    if (val.length >= 9) res += '-' + val.substring(8, 10);
    this.value = res;
});
</script>

</body>
</html>
