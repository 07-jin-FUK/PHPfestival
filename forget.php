<?php
// print_r($_SERVER);
// exit();
session_start();
include('functions.php');

// DB接続
$pdo = connect_to_db();


$errmessage = array();
$complete = false;

// フォームがPOSTされた場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = $_POST['mail'] ?? '';

    if (!$mail) {
        $errmessage[] = "Eメールを入力してください";
    } else if (strlen($mail) > 200) {
        $errmessage[] = "Eメールは200文字以内に指定してください";
    } else if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $errmessage[] = "Eメールアドレスは不正です";
    }

    if (empty($errmessage)) {
        // ユーザーが存在するかチェック
        $stmt = $pdo->prepare("SELECT * FROM users_table WHERE mail = :mail");
        $stmt->bindParam(':mail', $mail);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 新しいパスワードを生成
            $repass = bin2hex(random_bytes(5));
            $message = "パスワードを変更しました。\r\n" . $repass . "\r\n";

            // メールを送信
            if (mail($mail, 'パスワード変更しました。', $message)) {
                // パスワードをハッシュ化
                $hashedPassword = password_hash($repass, PASSWORD_DEFAULT);

                // データベースに新しいパスワードを保存
                $updateStmt = $pdo->prepare("UPDATE users_table SET password = :password WHERE mail = :mail");
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':mail', $mail);
                $updateStmt->execute();
                $complete = true;
            } else {
                $errmessage[] = "メールの送信に失敗しました。";
            }
        } else {
            $errmessage[] = "メールアドレスが正しくありません。";
        }
    }
} else {
    if (!isset($_SESSION['mail']) && $_SESSION['mail']) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        header("Location:main.php");
        exit();
    }
    $_POST = array();
    $mail = "";
    $pass = "";
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード再発行</title>
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
            background-image: url("./Img/tabi.jpg");
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
            <div class="mx-auto" style="width:400px">
                <?php
                if ($errmessage) {
                    echo '<div class="alert alert-danger" role="alert">';
                    echo implode('<br>', $errmessage);
                    echo '</div>';
                }
                ?>
                <?php
                if ($complete) {
                    echo 'パスワード再発行しました。';
                } else {
                ?>
                    <form action="./forget.php" method="POST">
                        <P>※パスワードを再発行します。※<br>仮パスワードをお送りしますのでメールアドレスを記載ください。
                        </P>
                        <div>
                            <label>
                                メールアドレス：
                                <input type="text" name="mail" class="form-control" value="<?php echo htmlspecialchars($mail, ENT_QUOTES, 'UTF-8'); ?>" style="width:400px" required><br>
                            </label>
                        </div>
                        <div class="button">
                            <input type="submit" value="再発行" class="btn btn-primary btn-lg"><br><br>
                        </div>
                    </form>
                    <p>新規会員登録の方は<a href="./register.php">こちら</a></p>
                    <p>ログインは<a href="./login.php">こちら</a></p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>

</html>