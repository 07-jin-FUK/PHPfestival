<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

$current_user = $_SESSION['username'];

$sql = 'SELECT * FROM tennis_reservations WHERE reserver = :current_user OR opponent = :current_user ORDER BY reservation_datetime DESC';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':current_user', $current_user, PDO::PARAM_STR);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約一覧</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 50px;
        }

        .table {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-name {
            display: inline-flex;
            align-items: center;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="mb-3">予約一覧</h1>
        </div>
        <?php if (count($reservations) > 0) : ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>場所</th>
                        <th>入力者</th>
                        <th>相手</th>
                        <th>日時</th>
                        <th>備考</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation) : ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['location']) ?></td>
                            <td>
                                <div class="user-name">
                                    <img src="<?= htmlspecialchars(get_user_picture($pdo, $reservation['reserver'])) ?>" alt="Profile Picture" class="profile-img">
                                    <?= htmlspecialchars($reservation['reserver']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="user-name">
                                    <img src="<?= htmlspecialchars(get_user_picture($pdo, $reservation['opponent'])) ?>" alt="Profile Picture" class="profile-img">
                                    <?= htmlspecialchars($reservation['opponent']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($reservation['reservation_datetime']) ?></td>
                            <td><?= htmlspecialchars($reservation['notes']) ?></td>
                            <td>
                                <form action="delete_reservation.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation['id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">削除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="text-center">現在、予約はありません。</p>
        <?php endif; ?>
        <div class="text-center">
            <a href="main.php" class="btn btn-primary mt-3">メイン画面に戻る</a>
        </div>
    </div>

    <?php
    // プロフィール画像を取得する関数
    function get_user_picture($pdo, $username)
    {
        $sql = 'SELECT picture FROM users_table WHERE username = :username';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['picture'] : './Img/defo.png';
    }
    ?>
</body>

</html>