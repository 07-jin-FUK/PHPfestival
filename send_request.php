<?php
session_start();
include('functions.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['id'])) {
        die('ログインしていません。');
    }

    $sender_id = $_SESSION['id'];
    $receiver_id = $_POST['receiver_id'];

    $pdo = connect_to_db();

    try {
        // 既に存在するリクエストをチェック
        $sql_check = 'SELECT * FROM match_requests WHERE sender_id = :sender_id AND receiver_id = :receiver_id AND status = "approved"';
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindValue(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt_check->bindValue(':receiver_id', $receiver_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $existing_request = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_request) {
            // 承認済みのリクエストが存在する場合、その情報をセッションに保存
            $approved_request = [
                'sender_picture' => $existing_request['sender_picture'],
                'sender_name' => $existing_request['sender_name'],
                'sender_bio' => $existing_request['sender_bio'],
                'sender_district' => $existing_request['sender_district'],
                'sender_interests' => $existing_request['sender_interests']
            ];

            if (!isset($_SESSION['approved_requests'])) {
                $_SESSION['approved_requests'] = [];
            }
            $_SESSION['approved_requests'][] = $approved_request;

            header('Location: match.php');
            exit();
        }

        // リクエストを新規送信
        $sql = 'INSERT INTO match_requests (sender_id, receiver_id, status) VALUES (:sender_id, :receiver_id, "pending")';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->bindValue(':receiver_id', $receiver_id, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: mailbox.php');
        exit();
    } catch (Exception $e) {
        echo 'リクエストの送信に失敗しました: ' . $e->getMessage();
    }
}
