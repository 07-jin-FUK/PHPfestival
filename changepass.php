<?php
session_start();
if (!isset($_SESSION['mail'])) {
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    //上でcharlistは省略
    header("Location: //$host$uri/login.php");
    exit();
}

include('functions.php');

// DB接続
$pdo = connect_to_db();
// print_r($_SERVER);
// exit();
$errmessage = array();
$complete = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPass = $_POST['pass'] ?? '';
    $newPass = $_POST['newpass'] ?? '';

    if (!$currentPass) {
        $errmessage[] = "現在のパスワードを入力してください";
    } else if (strlen($currentPass) > 100) {
        $errmessage[] = "現在のパスワードは100文字以内に指定してください";
    }

    if (!$newPass) {
        $errmessage[] = "新しいパスワードを入力してください";
    } else if (strlen($newPass) > 100) {
        $errmessage[] = "新しいパスワードは100文字以内に指定してください";
    }

    if (empty($errmessage)) {
        // データベースからユーザーの情報を取得
        $stmt = $pdo->prepare("SELECT * FROM users_table WHERE mail = :mail");
        $stmt->bindParam(':mail', $_SESSION['mail']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($currentPass, $user['password'])) {
            // 新しいパスワードをハッシュ化してデータベースに保存
            $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users_table SET password = :password WHERE mail = :mail");
            $updateStmt->bindParam(':password', $hashedPassword);
            $updateStmt->bindParam(':mail', $_SESSION['mail']);
            $updateStmt->execute();
            $complete = true;
        } else {
            $errmessage[] = "現在のパスワードが正しくありません。";
        }
    }
} else {
    $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード変更</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .haikei {
            background-image: url("./Img/fly.webp");
            width: 100%;
            height: 100vh;
            background-size: cover;
        }

        .login-container {
            margin-left: 38%;
            margin-top: 10%;
            position: relative;
            width: 500px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            text-align: center;
            z-index: 5;
        }
    </style>
</head>

<body>
    <div class="haikei">
        <div class="login-container">
            <div class="mx-auto">
                <?php
                if ($errmessage) {
                    echo '<div class="alert alert-danger" role="alert">';
                    echo implode('<br>', $errmessage);
                    echo '</div>';
                }
                ?>
                <?php if ($complete) { ?>
                    パスワードを変更しました。
                    <p>ホームに戻る<a href="./main.php">こちら</a></p>
                <?php } else { ?>
                    <form action="./changepass.php" method="POST">
                        <div>
                            <p style="font-size:20px;">パスワードを変更</p>
                            <label>
                                現在のパスワード：
                                <input type="password" name="pass" class="form-control" style="width:400px" required><br>
                                新しいパスワード：
                                <input type="password" name="newpass" class="form-control" style="width:400px" required><br>
                            </label>
                        </div>
                        <div class="button">
                            <input type="submit" value="変更" class="btn btn-primary btn-lg"><br><br>
                        </div>
                        <br>
                        <a href="./main.php">ホーム画面に戻る</a>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
</body>

</html>