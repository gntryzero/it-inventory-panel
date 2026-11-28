<?php
require 'connect.php';

// поиск. Если q пустое - показываем всё
$q = trim($_GET['q'] ?? '');
$where = '';

if ($q !== '') {
    // экранируем, чтобы sql-инъекций не было
    $q_safe = mysqli_real_escape_string($link, $q);
    $where = "WHERE name LIKE '%$q_safe%'
              OR serial_number LIKE '%$q_safe%'
              OR inventory_number LIKE '%$q_safe%'";
}

$sql = "SELECT * FROM assets $where ORDER BY id DESC";
$result = mysqli_query($link, $sql);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Реестр IT-оборудования</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Реестр IT-оборудования</h1>

    <form method="get" class="search-form">
        <input name="q" value="<?= esc($q) ?>" placeholder="Поиск по названию, серийнику...">
        <button type="submit" class="btn-search">Найти</button>
        <?php if ($q !== ''): ?>
            <a href="dashboard.php" style="align-self: center; margin-left: 10px;">Сбросить</a>
        <?php endif; ?>
    </form>

    <p><a href="editor.php" class="btn-add">+ Добавить оборудование</a></p>

    <table>
        <tr>
            <th>ID</th><th>Название</th><th>Серийный</th><th>Инвентарный</th>
            <th>Ответственный</th><th>Статус</th><th>Действия</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php 
                $retired = $row['is_retired'] == 1; 
                // Переводим статусы на русский для красоты
                $status_ru = [
                    'in_use' => 'В работе',
                    'storage' => 'На складе',
                    'repair' => 'В ремонте',
                    'retired' => 'Списано'
                ];
                $current_status = $retired ? 'Списано' : ($status_ru[$row['status']] ?? $row['status']);
            ?>
            <tr class="<?= $retired ? 'retired' : '' ?>">
                <td><?= esc($row['id']) ?></td>
                <td><strong><?= esc($row['name']) ?></strong></td>
                <td><?= esc($row['serial_number']) ?></td>
                <td><?= esc($row['inventory_number']) ?></td>
                <td><?= esc($row['assigned_to']) ?></td>
                <td><?= $current_status ?></td>
                <td>
                    <?php if (!$retired): ?>
                        <a href="editor.php?id=<?= (int)$row['id'] ?>">Изменить</a>
                        <form method="post" action="retire.php" style="display:inline"
                              onsubmit="return confirm('Списать оборудование?')">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="btn-retire">Списать</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
