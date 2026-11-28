<?php
require 'db.php';

// --- 1. СТАТИСТИКА ---
$stats_total   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM employees"))[0];
$stats_active  = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM employees WHERE status='active' AND is_fired=0"))[0];
$stats_fired   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM employees WHERE is_fired=1"))[0];

// --- 2. ПОИСК ---
$q = trim($_GET['q'] ?? '');
$where = '';
if ($q !== '') {
    $q_safe = mysqli_real_escape_string($link, $q);
    $where = "WHERE full_name LIKE '%$q_safe%' OR passport_number LIKE '%$q_safe%' OR phone_number LIKE '%$q_safe%'";
}

// --- 3. СОРТИРОВКА ---
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'DESC';
$allowed_sorts = ['id', 'full_name', 'status'];
if (!in_array($sort, $allowed_sorts)) $sort = 'id';
if ($order !== 'ASC' && $order !== 'DESC') $order = 'DESC';

$icon_id = ($sort == 'id') ? ($order == 'ASC' ? '↑' : '↓') : '↕';
$icon_name = ($sort == 'full_name') ? ($order == 'ASC' ? '↑' : '↓') : '↕';
$icon_status = ($sort == 'status') ? ($order == 'ASC' ? '↑' : '↓') : '↕';

// --- 4. ПАГИНАЦИЯ ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$count_query = mysqli_query($link, "SELECT COUNT(*) FROM employees $where");
$total_rows = mysqli_fetch_row($count_query)[0];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM employees $where ORDER BY $sort $order LIMIT $limit OFFSET $offset";
$result = mysqli_query($link, $sql);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Учет сотрудников</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Отдел кадров: Учет сотрудников</h1>

    <!-- Блок статистики -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-label">Всего сотрудников</span>
            <span class="stat-value"><?= $stats_total ?></span>
        </div>
        <div class="stat-card green">
            <span class="stat-label">Работают</span>
            <span class="stat-value"><?= $stats_active ?></span>
        </div>
        <div class="stat-card red">
            <span class="stat-label">Уволены</span>
            <span class="stat-value"><?= $stats_fired ?></span>
        </div>
    </div>

    <div class="top-actions">
        <a href="form.php" class="btn btn-primary">+ Добавить сотрудника</a>
        <a href="export.php" class="btn btn-secondary">Скачать в Excel</a>
        <a href="logs.php" class="btn btn-secondary">История действий</a>
    </div>

    <!-- Поиск -->
    <form method="get" class="search-form">
        <input type="hidden" name="sort" value="<?= esc($sort) ?>">
        <input type="hidden" name="order" value="<?= esc($order) ?>">
        
        <input name="q" value="<?= esc($q) ?>" placeholder="Поиск по ФИО, паспорту или телефону..." style="width: 300px;">
        <button type="submit" class="btn-search">Найти</button>
        <?php if ($q !== ''): ?>
            <a href="index.php" class="btn-edit">Сбросить</a>
        <?php endif; ?>
    </form>

    <!-- Таблица -->
    <table>
        <tr>
            <th><a href="?sort=id&order=<?= $sort == 'id' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">ID <span><?= $icon_id ?></span></a></th>
            <th><a href="?sort=full_name&order=<?= $sort == 'full_name' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">ФИО <span><?= $icon_name ?></span></a></th>
            <th>Отдел</th>
            <th>Должность</th>
            <th>Паспорт</th>
            <th>Телефон</th>
            <th>Зарплата</th>
            <th>Дата приема</th>
            <th><a href="?sort=status&order=<?= $sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC' ?>&q=<?= esc($q) ?>">Статус <span><?= $icon_status ?></span></a></th>
            <th>Действия</th>
        </tr>

        <?php if ($total_rows == 0): ?>
            <tr><td colspan="10" style="text-align:center; padding: 30px; color: #718096;">Ничего не найдено</td></tr>
        <?php endif; ?>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php 
                $fired = $row['is_fired'] == 1; 
                if ($fired) {
                    $badge_class = 'badge-fired'; $status_text = 'Уволен';
                } else {
                    switch ($row['status']) {
                        case 'active': $badge_class = 'badge-active'; $status_text = 'Работает'; break;
                        case 'vacation': $badge_class = 'badge-vacation'; $status_text = 'В отпуске'; break;
                        case 'sick': $badge_class = 'badge-sick'; $status_text = 'На больничном'; break;
                        default: $badge_class = 'badge-active'; $status_text = $row['status'];
                    }
                }
                
                // Красиво форматируем зарплату
                $salary_formatted = $row['salary'] ? number_format($row['salary'], 0, '.', ' ') . ' ₽' : '—';
                
                // Красиво форматируем дату (из 2021-03-15 делаем 15.03.2021)
                $date_formatted = $row['hire_date'] ? date('d.m.Y', strtotime($row['hire_date'])) : '—';
            ?>
            <tr class="<?= $fired ? 'retired' : '' ?>">
                <td><?= esc($row['id']) ?></td>
                <td><strong><?= esc($row['full_name']) ?></strong></td>
                <td><?= esc($row['department']) ?></td>
                <td><?= esc($row['position']) ?></td>
                <td><?= esc($row['passport_number']) ?></td>
                <td><?= esc($row['phone_number']) ?></td>
                <td><?= $salary_formatted ?></td>
                <td><?= $date_formatted ?></td>
                <td><span class="badge <?= $badge_class ?>"><?= $status_text ?></span></td>
                <td>
                    <?php if (!$fired): ?>
                        <a href="form.php?id=<?= (int)$row['id'] ?>" class="btn-edit">Изменить</a>
                        <form method="post" action="fire.php" style="display:inline" onsubmit="return confirm('Уволить сотрудника?')">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="btn-retire">Уволить</button>
                        </form>
                    <?php else: ?> — <?php endif; ?>
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
