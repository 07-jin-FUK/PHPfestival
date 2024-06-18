<?php
session_start();
include('functions.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['id'])) {
        die('ログインしていません。');
    }

    $user_id = $_SESSION['id'];
    $request_id = $_POST['request_id'];

    $pdo = connect_to_db();

    try {
        $sql = 'DELETE FROM match_requests WHERE id = :request_id AND (sender_id = :user_id OR receiver_id = :user_id)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: mailbox.php');
        exit();
    } catch (Exception $e) {
        echo 'リクエストの削除に失敗しました: ' . $e->getMessage();
    }
}
