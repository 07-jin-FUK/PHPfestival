<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

$request_id = $_POST['request_id'];
$action = $_POST['action'];

if ($action === 'approved') {
    $sql = 'UPDATE match_requests SET status = "approved" WHERE id = :request_id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();

    // 承認されたリクエスト情報をセッションに保存
    $approved_request = [
        'sender_picture' => $_POST['sender_picture'],
        'sender_name' => $_POST['sender_name'],
        'sender_bio' => $_POST['sender_bio'],
        'sender_district' => $_POST['sender_district'],
        'sender_interests' => $_POST['sender_interests']
    ];

    if (!isset($_SESSION['approved_requests'])) {
        $_SESSION['approved_requests'] = [];
    }
    $_SESSION['approved_requests'][] = $approved_request;

    header('Location: match.php');
    exit();
} elseif ($action === 'denied') {
    $sql = 'UPDATE match_requests SET status = "denied" WHERE id = :request_id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
}

header('Location: mailbox.php');
exit();
