<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $courtName = $_POST['courtName'];
    $courtVicinity = $_POST['courtVicinity'];
    $reserver = $_POST['reserver'];
    $date = $_POST['date'];
    $opponent = $_POST['opponent'];
    $notes = $_POST['notes'];

    $sql = 'INSERT INTO reservations (court_name, court_vicinity, reserver, date, opponent, notes) VALUES (:court_name, :court_vicinity, :reserver, :date, :opponent, :notes)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':court_name', $courtName, PDO::PARAM_STR);
    $stmt->bindValue(':court_vicinity', $courtVicinity, PDO::PARAM_STR);
    $stmt->bindValue(':reserver', $reserver, PDO::PARAM_STR);
    $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    $stmt->bindValue(':opponent', $opponent, PDO::PARAM_INT);
    $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
    $stmt->execute();

    header('Location: main.php');
    exit();
}
