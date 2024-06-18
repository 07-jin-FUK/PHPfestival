<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['id'];
    $sender_id = $data['user_id'];

    $sql_update_read = 'UPDATE messages SET viewed = 1 WHERE receiver_id = :receiver_id AND sender_id = :sender_id AND viewed = 0';
    $stmt_update_read = $pdo->prepare($sql_update_read);
    $stmt_update_read->bindValue(':receiver_id', $user_id, PDO::PARAM_INT);
    $stmt_update_read->bindValue(':sender_id', $sender_id, PDO::PARAM_INT);
    $stmt_update_read->execute();
}
