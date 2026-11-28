<?php
require 'connect.php';

$id = (int)($_GET['id'] ?? 0);
$error = '';
$asset = [
    'name' => '',
    'serial_number' => '',
    'inventory_number' => '',
    'assigned_to' => '',
    'status' => 'in_use',
    'is_retired' => 0
];

if ($id > 0) {
    $r = mysqli_query($link, "SELECT * FROM assets WHERE id = $id");
    $asset = mysqli_fetch_assoc($r);
    if (!$asset) die('Оборудование не найдено');
    if ($asset['is_retired']) die('Списанное оборудование нельзя редактировать');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $serial = trim($_POST['serial_number'] ?? '');
    $inv = trim($_POST['inventory_number'] ?? '');
    $assigned = trim($_POST['assigned_to'] ?? '');
    $status = $_POST['status'] ?? 'in_use';

    if ($name === '' || $serial === '' || $inv === '') {
        $error = 'Заполните название, серийный и инвентарный номера';
    } elseif (mb_strlen($serial) < 5) {
        $error = 'Серийный номер слишком короткий';
    } else {
        $name_e = mysqli_real_escape_string($link, $name);
        $serial_e = mysqli_real_escape_string($link, $serial);
        $inv_e = mysqli_real_escape_string($link, $inv);
        $assigned_e = mysqli_real_escape_string($link, $assigned);
        $status_e = mysqli_real_escape_string($link, $status);

        if ($id > 0) {
            mysqli_query($link, "UPDATE assets SET
                name='$name_e',
                serial_number='$serial_e',
                inventory_number='$inv_e',
                assigned_to='$assigned_e',
                status='$status_e'
                WHERE id=$id AND is_retired=0");
        } else {
            mysqli_query($link, "INSERT INTO assets
                (name, serial_number, inventory_number, assigned_to, status)
                VALUES ('$name_e', '$serial_e', '$inv_e', '$assigned_e', '$status_e')");
        }

        header('Location: dashboard.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="ru">
<meta charset="utf-8">
<title><?= $id ? 'Редактирование' : 'Добавление' ?> оборудования</title>
<h1><?= $id ? 'Редактирование' : 'Добавление' ?> оборудования</h1>

<?php if ($error): ?>
  <p style="color:red"><?= esc($error) ?></p>
<?php endif; ?>

<form method="post">
  <p>Название:<br>
    <input name="name" value="<?= esc($asset['name']) ?>" required>
  </p>
  <p>Серийный номер:<br>
    <input name="serial_number" value="<?= esc($asset['serial_number']) ?>" required>
  </p>
  <p>Инвентарный номер:<br>
    <input name="inventory_number" value="<?= esc($asset['inventory_number']) ?>" required>
  </p>
  <p>Ответственный:<br>
    <input name="assigned_to" value="<?= esc($asset['assigned_to']) ?>">
  </p>
  <p>Статус:<br>
    <select name="status">
      <option value="in_use" <?= $asset['status'] === 'in_use' ? 'selected' : '' ?>>В работе</option>
      <option value="storage" <?= $asset['status'] === 'storage' ? 'selected' : '' ?>>На складе</option>
      <option value="repair" <?= $asset['status'] === 'repair' ? 'selected' : '' ?>>В ремонте</option>
    </select>
  </p>
  <button>Сохранить</button>
  <a href="dashboard.php">Назад</a>
</form>
