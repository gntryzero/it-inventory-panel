<?php
require 'connect.php';

// --- 1. СТАТИСТИКА ---
$stats_total   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM assets"))[0];
$stats_in_use  = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM assets WHERE status='in_use' AND is_retired=0"))[0];
$stats_retired = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM assets WHERE is_retired=1"))[0];

// --- 2. ПОИСК ---
$q = trim($_GET['q'] ?? '');
$where = '';
if ($q !== '') {
    $q_safe = mysqli_real_escape_string($link, $q);
    $where = "WHERE name LIKE '%$q_safe%' OR serial_number LIKE '%$q_safe%' OR inventory_number LIKE '%$q_safe%'";
}

// --- 3. СОРТИРОВКА ---
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'DESC';
$allowed_sorts = ['id', 'name', 'status'];
if (!in_array($sort, $allowed_sorts)) $sort = 'id';
if ($order !== 'ASC' && $order !== 'DESC') $order = 'DESC';

// Иконки для сортировки
$icon_id = ($sort == 'id') ? ($order == 'ASC' ? '↑' : '↓') : '↕';
$icon_name = ($sort == 'name') ? ($order == 'ASC' ? '↑' : '↓') : '↕';
$icon_status = ($sort == 'status') ? ($order == 'ASC' ? '↑' : '↓') : '↕';

// --- 4. ПАГИНАЦИЯ ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$count_query = mysqli_query($link, "SELECT COUNT(*) FROM assets $where");
$total_rows = mysqli_fetch_row($count_query)[0];
$total_pages = ceil($total_rows / $limit);

// Основной запрос
$sql = "SELECT * FROM assets $where ORDER BY $sort $order LIMIT $limit OFFSET $offset";
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

    <!-- Блок статистики -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-label">Всего единиц</span>
            <span class="stat-value"><?= $stats_total ?></span>
        </div>
        <div class="stat-card green">
            <span class="stat-label">В работе</span>
            <span class="stat-value"><?= $stats_in_use ?></span>
        </div>
        <div class="stat-card red">
            <span class="stat-label">Списано</span>
            <span class="stat-value"><?= $stats_retired ?></span>
        </div>
    </div>

    <!-- Кнопки управления -->
    <div class="top-actions">
        <a href="editor.php" class="btn btn-primary">+ Добавить оборудование</a>
        <a href="export.php" class="btn btn-secondary">Скачать в Excel</a>
        <a href="logs.php" class="btn btn-secondary">История действий</a>
    </div>

    <!-- Поиск -->
    <form method="get" class="search-form">
        <input type="hidden" name="sort" value="<?= esc($sort) ?>">
        <input type="hidden" name="order" value="<?= esc($order) ?>">
        
        <input name="q" value="<?= esc($q) ?>" placeholder="Поиск по названию или серийнику..." style="width: 300px;">
        <button type="submit" class="btn-search">Найти</button>
        <?php if ($q !== ''): ?>
            <a href="dashboard.php" class="btn-edit">Сбросить</a>
        <?php endif; ?>
    </form>

    <!-- Таблица -->
    <table>
        <tr>
            <th><a href="?sort=id&order=<?= $sort == 'id' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">ID <span><?= $icon_id ?></span></a></th>
            <th><a href="?sort=name&order=<?= $sort == 'name' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">Название <span><?= $icon_name ?></span></a></th>
            <th>Серийный</th>
            <th>Инвентарный</th>
            <th>Ответственный</th>
            <th><a href="?sort=status&order=<?= $sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">Статус <span><?= $icon_status ?></span></a></th>
            <th>Действия</th>
        </tr>

        <?php if ($total_rows == 0): ?>
            <tr><td colspan="7" style="text-align:center; padding: 30px; color: #718096;">Ничего не найдено</td></tr>
        <?php endif; ?>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php 
                $retired = $row['is_retired'] == 1; 
                $status_ru = ['in_use' => 'В работе', 'storage' => 'На складе', 'repair' => 'В ремонте', 'retired' => 'Списано'];
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
                        <a href="editor.php?id=<?= (int)$row['id'] ?>" class="btn-edit">Изменить</a>
                        <form method="post" action="retire.php" style="display:inline" onsubmit="return confirm('Списать оборудование?')">
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

    <!-- Пагинация -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&sort=<?= esc($sort) ?>&order=<?= esc($order) ?>&q=<?= esc($q) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
