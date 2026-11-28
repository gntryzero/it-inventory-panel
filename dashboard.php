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
// Белый список колонок, чтобы нельзя было сделать sql-инъекцию через сортировку
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'DESC';
$allowed_sorts = ['id', 'name', 'status'];
if (!in_array($sort, $allowed_sorts)) $sort = 'id';
if ($order !== 'ASC' && $order !== 'DESC') $order = 'DESC';

// --- 4. ПАГИНАЦИЯ (по 5 записей) ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Считаем сколько всего страниц
$count_query = mysqli_query($link, "SELECT COUNT(*) FROM assets $where");
$total_rows = mysqli_fetch_row($count_query)[0];
$total_pages = ceil($total_rows / $limit);

// Основной запрос с сортировкой и лимитом
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

    <form method="get" class="search-form">
        <!-- Скрытые поля, чтобы при поиске не сбрасывалась сортировка -->
        <input type="hidden" name="sort" value="<?= esc($sort) ?>">
        <input type="hidden" name="order" value="<?= esc($order) ?>">
        
        <input name="q" value="<?= esc($q) ?>" placeholder="Поиск по названию, серийнику...">
        <button type="submit" class="btn-search">Найти</button>
        <?php if ($q !== ''): ?>
            <a href="dashboard.php" style="align-self: center; margin-left: 10px;">Сбросить</a>
        <?php endif; ?>
    </form>

    <p style="display: flex; gap: 10px;">
        <a href="editor.php" class="btn-add" style="margin-bottom: 0;">+ Добавить оборудование</a>
        <a href="export.php" class="btn-add" style="background: #718096; margin-bottom: 0;">Скачать в Excel</a>
        <a href="logs.php" class="btn-add" style="background: #4a5568; margin-bottom: 0;">История действий</a>
    </p>

    <table>
        <tr>
            <th><a href="?sort=id&order=<?= $sort == 'id' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">ID <span class="sort-icon">↕</span></a></th>
            <th><a href="?sort=name&order=<?= $sort == 'name' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">Название <span class="sort-icon">↕</span></a></th>
            <th>Серийный</th>
            <th>Инвентарный</th>
            <th>Ответственный</th>
            <th><a href="?sort=status&order=<?= $sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">Статус <span class="sort-icon">↕</span></a></th>
            <th>Действия</th>
        </tr>

        <?php if ($total_rows == 0): ?>
            <tr><td colspan="7" style="text-align:center;">Ничего не найдено</td></tr>
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
                        <a href="editor.php?id=<?= (int)$row['id'] ?>">Изменить</a>
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
