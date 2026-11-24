<?php
require 'connect.php';

$q = trim($_GET['q'] ?? '');
$where = '';

if ($q !== '') {
    $safe = mysqli_real_escape_string($link, $q);
    $where = "WHERE name LIKE '%$safe%'
              OR serial_number LIKE '%$safe%'
              OR inventory_number LIKE '%$safe%'";
}

$res = mysqli_query($link, "SELECT * FROM assets $where ORDER BY id DESC");
?>
<!doctype html>
<html lang="ru">
<meta charset="utf-8">
<title>Реестр IT-оборудования</title>
<h1>Реестр IT-оборудования</h1>

<form method="get">
  <input name="q" value="<?= esc($q) ?>" placeholder="Поиск">
  <button>Найти</button>
</form>

<p><a href="editor.php">+ Добавить оборудование</a></p>

<table border="1" cellpadding="6">
<tr>
  <th>ID</th><th>Название</th><th>Серийный</th><th>Инвентарный</th>
  <th>Ответственный</th><th>Статус</th><th>Действия</th>
</tr>

<?php while ($row = mysqli_fetch_assoc($res)): ?>
<tr style="<?= $row['is_retired'] ? 'color:#888;background:#f3f3f3' : '' ?>">
  <td><?= esc($row['id']) ?></td>
  <td><?= esc($row['name']) ?></td>
  <td><?= esc($row['serial_number']) ?></td>
  <td><?= esc($row['inventory_number']) ?></td>
  <td><?= esc($row['assigned_to']) ?></td>
  <td><?= $row['is_retired'] ? 'Списано' : esc($row['status']) ?></td>
  <td>
    <?php if (!$row['is_retired']): ?>
      <a href="editor.php?id=<?= (int)$row['id'] ?>">Изменить</a>
      <form method="post" action="retire.php" style="display:inline">
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <button onclick="return confirm('Списать оборудование?')">Списать</button>
      </form>
    <?php else: ?>
      —
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>
</table>
