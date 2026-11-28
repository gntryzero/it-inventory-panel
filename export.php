<?php
require 'db.php';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=staff_report.csv');
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Добавили новые заголовки
fputcsv($output, ['ID', 'ФИО', 'Отдел', 'Должность', 'Паспорт', 'Телефон', 'Адрес', 'Зарплата', 'Дата приема', 'Статус'], ';');

$result = mysqli_query($link, "SELECT * FROM employees ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $status = $row['is_fired'] ? 'Уволен' : $row['status'];
    fputcsv($output, [
        $row['id'], 
        $row['full_name'], 
        $row['department'], 
        $row['position'], 
        $row['passport_number'], 
        $row['phone_number'], 
        $row['address'], 
        $row['salary'], 
        $row['hire_date'], 
        $status
    ], ';');
}
fclose($output);
log_action($link, "Скачал отчет в Excel");
exit;
?>
