<?php

// function connect_to_db()
// {
//     $dbn = 'mysql:dbname=gs_l10_07;charset=utf8mb4;port=3306;host=localhost';
//     $user = 'root';
//     $pwd = '';

//     try {
//         return new PDO($dbn, $user, $pwd);
//     } catch (PDOException $e) {
//         echo json_encode(["db error" => "{$e->getMessage()}"]);
//         exit();
//     }
// }


// function fetch_user_info($pdo, $username, $mail)
// {
//     $sql = 'SELECT * FROM users_table WHERE username = :username AND mail = :mail';
//     $stmt = $pdo->prepare($sql);
//     $stmt->bindValue(':username', $username, PDO::PARAM_STR);
//     $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
//     $stmt->execute();
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }



function connect_to_db()
{
    $dbn = getenv('DB_DSN') ?: 'mysql:dbname=gs_l10_07;charset=utf8mb4;port=3306;host=localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pwd = getenv('DB_PASSWORD') ?: '';

    try {
        return new PDO($dbn, $user, $pwd);
    } catch (PDOException $e) {
        if (getenv('APP_ENV') === 'production') {
            error_log($e->getMessage(), 0);
            exit('データベース接続エラーが発生しました。');
        } else {
            echo json_encode(["db error" => "{$e->getMessage()}"]);
            exit();
        }
    }
}

function fetch_user_info($pdo, $username, $mail)
{
    $sql = 'SELECT * FROM users_table WHERE username = :username AND mail = :mail';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
