<?php
session_start();
include 'config.php';

if (!isset($_GET['id'])) {
    header('Location: reports_admin.php');
    exit;
}

$id = (int)$_GET['id'];

$sql = "DELETE FROM incidents_reports WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

header('Location: reports_admin.php');
exit;
?>
