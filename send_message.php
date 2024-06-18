<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['id'])) {
        die('ログインしていません。');
    }

    $sender_id = $_SESSION['id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];

    try {
        $sql = 'INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->bindValue(':receiver_id', $receiver_id, PDO::PARAM_INT);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->execute();

        // メッセージ送信成功フラグを設定
        $_SESSION['last_message_sent'] = true;

        header('Location: search.php');
        exit();
    } catch (Exception $e) {
        echo 'メッセージの送信に失敗しました: ' . $e->getMessage();
    }
}
