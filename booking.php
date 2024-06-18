<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = $_POST['location'];
    $reserver = $_POST['user'];
    $opponent = $_POST['partner'];
    $reservation_datetime = $_POST['date'];
    $notes = $_POST['notes'];

    $sql = 'INSERT INTO tennis_reservations (location, reserver, opponent, reservation_datetime, notes) VALUES (:location, :reserver, :opponent, :reservation_datetime, :notes)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':location', $location, PDO::PARAM_STR);
    $stmt->bindValue(':reserver', $reserver, PDO::PARAM_STR);
    $stmt->bindValue(':opponent', $opponent, PDO::PARAM_STR);
    $stmt->bindValue(':reservation_datetime', $reservation_datetime, PDO::PARAM_STR);
    $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
    $stmt->execute();

    header('Location: reservations.php');
    exit();
}
