<?php
require 'connect.php';

$result = mysqli_query($link, "SELECT * FROM action_logs ORDER BY id DESC LIMIT 50");
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>История действий</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>История действий</h1>
    <p><a href="dashboard.php">← Вернуться на главную</a></p>

    <table>
        <tr>
            <th style="width: 200px;">Дата и время</th>
            <th>Действие</th>
        </tr>
        <?php if (mysqli_num_rows($result) == 0): ?>
            <tr><td colspan="2">Пока нет записей</td></tr>
        <?php endif; ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= esc($row['created_at']) ?></td>
                <td><?= esc($row['action']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
