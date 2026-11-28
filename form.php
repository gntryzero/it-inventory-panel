<?php
require 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Заготовка со всеми полями
$emp = [
    'full_name'       => '',
    'passport_number' => '',
    'phone_number'    => '',
    'position'        => '',
    'department'      => '',
    'address'         => '',
    'salary'          => '',
    'hire_date'       => date('Y-m-d'),
    'status'          => 'active'
];

if ($id > 0) {
    $r = mysqli_query($link, "SELECT * FROM employees WHERE id = $id LIMIT 1");
    $emp = mysqli_fetch_assoc($r);
    if (!$emp) die('Нет такого сотрудника');
    if ($emp['is_fired']) die('Уволенного нельзя редактировать');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name       = trim($_POST['full_name'] ?? '');
    $passport   = trim($_POST['passport_number'] ?? '');
    $phone      = trim($_POST['phone_number'] ?? '');
    $position   = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $salary     = trim($_POST['salary'] ?? '');
    $hire_date  = trim($_POST['hire_date'] ?? '');
    $status     = $_POST['status'] ?? 'active';

    if ($name === '' || $passport === '' || $phone === '') {
        $error = 'ФИО, паспорт и телефон — обязательные поля';
    } else {
        $n  = mysqli_real_escape_string($link, $name);
        $p  = mysqli_real_escape_string($link, $passport);
        $ph = mysqli_real_escape_string($link, $phone);
        $pos= mysqli_real_escape_string($link, $position);
        $dep= mysqli_real_escape_string($link, $department);
        $adr= mysqli_real_escape_string($link, $address);
        $sal= $salary === '' ? 'NULL' : (float)$salary;
        $hd = mysqli_real_escape_string($link, $hire_date);
        $st = mysqli_real_escape_string($link, $status);

        if ($id > 0) {
            mysqli_query($link, "UPDATE employees SET 
                full_name='$n', passport_number='$p', phone_number='$ph', 
                position='$pos', department='$dep', address='$adr', 
                salary=$sal, hire_date='$hd', status='$st' 
                WHERE id=$id AND is_fired=0");
            log_action($link, "Отредактировал сотрудника: $name");
        } else {
            mysqli_query($link, "INSERT INTO employees 
                (full_name, passport_number, phone_number, position, department, address, salary, hire_date, status) 
                VALUES 
                ('$n', '$p', '$ph', '$pos', '$dep', '$adr', $sal, '$hd', '$st')");
            log_action($link, "Добавил нового сотрудника: $name");
        }
        header('Location: index.php'); exit;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= $id ? 'Редактирование' : 'Добавление' ?> сотрудника</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-width: 800px; }
        .form-group { margin-bottom: 5px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; }
    </style>
</head>
<body>
<div class="container">
    <h1><?= $id ? 'Редактирование' : 'Добавление' ?> сотрудника</h1>
    <?php if ($error): ?><p class="error"><?= esc($error) ?></p><?php endif; ?>
    
    <form method="post">
        <div class="form-grid">
            <!-- Полное ФИО на всю ширину -->
            <div class="form-group full-width">
                <label>ФИО:</label>
                <input name="full_name" id="full_name" value="<?= esc($emp['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Отдел:</label>
                <input name="department" id="department" value="<?= esc($emp['department']) ?>" placeholder="Например: Бухгалтерия">
            </div>
            <div class="form-group">
                <label>Должность:</label>
                <input name="position" id="position" value="<?= esc($emp['position']) ?>">
            </div>
            
            <div class="form-group">
                <label>Паспорт:</label>
                <input name="passport_number" id="passport" value="<?= esc($emp['passport_number']) ?>" placeholder="1234 567890" required>
            </div>
            <div class="form-group">
                <label>Телефон:</label>
                <input name="phone_number" id="phone" value="<?= esc($emp['phone_number']) ?>" placeholder="+7 (999) 123-45-67" required>
            </div>

            <div class="form-group full-width">
                <label>Адрес проживания:</label>
                <input name="address" value="<?= esc($emp['address']) ?>" placeholder="г. Москва, ул. Ленина, д. 1, кв. 2">
            </div>

            <div class="form-group">
                <label>Размер зарплаты (₽):</label>
                <input name="salary" id="salary" value="<?= esc($emp['salary']) ?>" placeholder="50000">
            </div>
            <div class="form-group">
                <label>Дата принятия на работу:</label>
                <input type="date" name="hire_date" value="<?= esc($emp['hire_date']) ?>">
            </div>

            <div class="form-group full-width">
                <label>Статус:</label>
                <select name="status">
                    <option value="active" <?= $emp['status'] === 'active' ? 'selected' : '' ?>>Работает</option>
                    <option value="vacation" <?= $emp['status'] === 'vacation' ? 'selected' : '' ?>>В отпуске</option>
                    <option value="sick" <?= $emp['status'] === 'sick' ? 'selected' : '' ?>>На больничном</option>
                </select>
            </div>
        </div>

        <br>
        <button type="submit" class="btn-save">Сохранить</button>
        <a href="index.php" style="margin-left: 15px;">Назад к списку</a>
    </form>
</div>

<script>
// Маска ФИО
document.getElementById('full_name').addEventListener('input', function() {
    this.value = this.value.replace(/[^а-яА-ЯёЁa-zA-Z\s\-]/g, '');
});
// Маска Должности и Отдела (буквы и пробелы)
document.getElementById('position').addEventListener('input', function() {
    this.value = this.value.replace(/[^а-яА-ЯёЁa-zA-Z\s\-\.]/g, '');
});
document.getElementById('department').addEventListener('input', function() {
    this.value = this.value.replace(/[^а-яА-ЯёЁa-zA-Z\s\-]/g, '');
});
// Маска Паспорта
document.getElementById('passport').addEventListener('input', function() {
    let val = this.value.replace(/\D/g, ''); 
    if (val.length > 4) { this.value = val.slice(0, 4) + ' ' + val.slice(4, 10); } 
    else { this.value = val; }
});
// Маска Телефона
document.getElementById('phone').addEventListener('input', function() {
    let val = this.value.replace(/\D/g, '');
    if (val.length === 0) { this.value = ''; return; }
    if (val[0] === '7' || val[0] === '8') { val = val.substring(1); }
    let res = '+7 ';
    if (val.length > 0) res += '(' + val.substring(0, 3);
    if (val.length >= 4) res += ') ' + val.substring(3, 6);
    if (val.length >= 7) res += '-' + val.substring(6, 8);
    if (val.length >= 9) res += '-' + val.substring(8, 10);
    this.value = res;
});
// Маска Зарплаты (только цифры)
document.getElementById('salary').addEventListener('input', function() {
    this.value = this.value.replace(/[^\d]/g, '');
});
</script>
</body>
</html>
