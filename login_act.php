<?php
// セッション開始
session_start();
include('functions.php'); // データベース接続のための関数が含まれるファイルをインクルード

$mail = $_POST['mail'] ?? '';
$password = $_POST['password'] ?? '';

// DB接続
$pdo = connect_to_db();

// ユーザ情報を取得するSQL
$sql = 'SELECT * FROM users_table WHERE mail=:mail AND deleted_at IS NULL';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':mail', $mail, PDO::PARAM_STR);

try {
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // ユーザー情報を取得
} catch (PDOException $e) {
    echo json_encode(["sql error" => "{$e->getMessage()}"]);
    exit();
}

// ユーザ有無とパスワードの検証
if (!$user || !password_verify($password, $user['password'])) {
    echo '<p>メールアドレスもしくはパスワードが一致していません</p>';
    echo '<a href="./login.php">login</a>';
    exit();
} else {
    // セッション変数に必要な情報を保存
    $_SESSION = array();
    $_SESSION['session_id'] = session_id();
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['mail'] = $user['mail'];
    $_SESSION['id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    header("Location:main.php");
    exit();
}
