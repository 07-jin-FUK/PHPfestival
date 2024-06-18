<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'];

    $sql = 'DELETE FROM tennis_reservations WHERE id = :reservation_id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':reservation_id', $reservation_id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: reservations.php');
    exit();
}
