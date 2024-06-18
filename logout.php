<?php
session_start();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <style>
        body {
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .popup {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            padding: 20px;
            width: 600px;
            transform: scale(0.5);
            opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        .popup img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .popup.visible {
            transform: scale(1);
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="popup" id="logoutPopup">
        <img src="./Img/tennis.avif" alt="Goodbye Image">
        <p>ログアウトしました。次に会う日を楽しみにしています！</p>
        <a href="./login.php" class="btn btn-primary">ログインへ</a>
    </div>

    <script>
        // ページがロードされた時にポップアップを表示
        window.onload = function() {
            document.getElementById('logoutPopup').classList.add('visible');
        };
    </script>
</body>

</html>