<?php
session_start();
include('functions.php');

// 初期化
$errmessage = array();
$username = '';
$mail = '';
$password = '';
$pass2 = '';

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $mail = $_POST["mail"];
    $pass2 = $_POST["pass2"];

    // バリデーション
    if (!$mail) {
        $errmessage[] = "Eメールを入力してください";
    } else if (strlen($mail) > 200) {
        $errmessage[] = "Eメールは200文字以内に指定してください";
    } else if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $errmessage[] = "Eメールアドレスは不正です";
    }

    if (!$password) {
        $errmessage[] = "パスワードを入力してください";
    } else if (strlen($password) > 100) {
        $errmessage[] = "パスワードは100文字以内に指定してください";
    }

    if ($password !== $pass2) {
        $errmessage[] = "確認用パスワードが一致していません";
    }

    if (empty($errmessage)) {
        // エラーがない場合のみデータベース操作を行う
        $pdo = connect_to_db();

        // メールアドレスの重複チェック
        $sql = 'SELECT COUNT(*) FROM users_table WHERE mail=:mail';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);

        try {
            $status = $stmt->execute();
        } catch (PDOException $e) {
            echo json_encode(["sql error" => "{$e->getMessage()}"]);
            exit();
        }

        if ($stmt->fetchColumn() > 0) {
            echo '<p>すでに登録されているユーザです．</p>';
            echo '<a href="./login.php">login</a>';
            exit();
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // interestsカラムにデフォルト値を設定
        $sql = 'INSERT INTO users_table (id, username, password, mail, is_admin, created_at, updated_at, deleted_at, interests) VALUES (NULL, :username, :password, :mail, 0, now(), now(), NULL, :interests)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
        $stmt->bindValue(':interests', '[]', PDO::PARAM_STR); // 空のJSON配列を設定

        try {
            $status = $stmt->execute();
?>
            <!DOCTYPE html>
            <html lang="ja">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>登録成功</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                        background: #f8f9fa;
                    }

                    .message-container {
                        position: relative;
                        width: 400px;
                        background-color: white;
                        padding: 30px;
                        border-radius: 10px;
                        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
                        text-align: center;
                    }

                    .message-container .icon {
                        font-size: 50px;
                        color: #28a745;
                    }
                </style>
                <script>
                    // 3秒後にログインページへリダイレクト
                    setTimeout(function() {
                        window.location.href = "./login.php";
                    }, 3000);
                </script>
            </head>

            <body>
                <div class="message-container">
                    <div class="icon">
                        ✔️
                    </div>
                    <h1>登録が成功しました</h1>
                    <p>3秒後にログインページにリダイレクトされます。</p>
                    <p>すぐに移動しない場合は <a href="./login.php">こちら</a> をクリックしてください。</p>
                </div>
            </body>

            </html>
<?php
            exit();
        } catch (PDOException $e) {
            echo json_encode(["sql error" => "{$e->getMessage()}"]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }

        .haikei {
            background-image: url("./Img/新規.jpg");
            width: 100%;
            height: 100vh;
            background-size: cover;
            background-position: center;

            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .login-container {
            position: relative;
            width: 500px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            text-align: center;
            z-index: 5;
        }

        .login-form {
            width: 100%;
        }

        .login-form p {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .login-form .form-control {
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .button {
            text-align: center;
        }

        .login-form a {
            color: #007bff;
            text-decoration: none;
        }

        .login-form a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="haikei"></div>
    <div class="login-container">
        <?php
        if ($errmessage) {
            echo '<div class="alert alert-danger" role="alert">';
            echo implode('<br>', $errmessage);
            echo '</div>';
        }
        ?>

        <form action="./register.php" method="POST" class="login-form">
            <P>新規会員登録</P>
            <div>
                <label>
                    名前：
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" class="form-control"><br>
                </label>
            </div>
            <div>
                <label>
                    メールアドレス：
                    <input type="text" name="mail" value="<?php echo htmlspecialchars($mail, ENT_QUOTES, 'UTF-8'); ?>" class="form-control"><br>
                </label>
            </div>
            <div>
                <label>
                    パスワード：
                    <input type="password" name="password" class="form-control"><br>
                </label>
            </div>
            <div>
                <label>
                    パスワード(確認)：
                    <input type="password" name="pass2" class="form-control"><br>
                </label>
            </div>
            <div class="button">
                <input type="submit" value="登録" class="btn btn-primary btn-lg"><br><br>
            </div>
        </form>

        <p>すでに登録済みの方は<a href="./login.php">こちら</a></p>
    </div>
</body>

</html>