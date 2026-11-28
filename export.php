<?php
require 'connect.php';

// Говорим браузеру, что это файл для скачивания
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_report.csv');

$output = fopen('php://output', 'w');

// BOM для корректного отображения кириллицы в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Заголовки
fputcsv($output, ['ID', 'Название', 'Серийный номер', 'Инвентарный', 'Ответственный', 'Статус'], ';');

$result = mysqli_query($link, "SELECT * FROM assets ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $status = $row['is_retired'] ? 'Списано' : $row['status'];
    fputcsv($output, [
        $row['id'], $row['name'], $row['serial_number'], 
        $row['inventory_number'], $row['assigned_to'], $status
    ], ';');
}
fclose($output);

// Пишем в лог
log_action($link, "Скачал отчет в Excel");
exit;
?>
