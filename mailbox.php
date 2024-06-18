<?php
session_start();
include('functions.php');

$user_id = $_SESSION['id'];

$pdo = connect_to_db();

// 未読メッセージの数を取得
$sql_unread_count = 'SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = :user_id AND viewed = 0';
$stmt_unread_count = $pdo->prepare($sql_unread_count);
$stmt_unread_count->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_unread_count->execute();
$unread_count_result = $stmt_unread_count->fetch(PDO::FETCH_ASSOC);
$unread_count = $unread_count_result['unread_count'];

// 送信したメッセージを取得
$sql_sent = 'SELECT m.*, u.username AS receiver_name, u.picture AS receiver_picture, u.bio AS receiver_bio, u.district AS receiver_district, u.interests AS receiver_interests FROM messages m JOIN users_table u ON m.receiver_id = u.id WHERE m.sender_id = :user_id ORDER BY m.created_at DESC';
$stmt_sent = $pdo->prepare($sql_sent);
$stmt_sent->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_sent->execute();
$sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);

// 受信したメッセージを取得
$sql_received = 'SELECT m.*, u.username AS sender_name, u.picture AS sender_picture, u.bio AS sender_bio, u.district AS sender_district, u.interests AS sender_interests FROM messages m JOIN users_table u ON m.sender_id = u.id WHERE m.receiver_id = :user_id ORDER BY m.created_at DESC';
$stmt_received = $pdo->prepare($sql_received);
$stmt_received->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_received->execute();
$received_messages = $stmt_received->fetchAll(PDO::FETCH_ASSOC);

// 既読フラグを更新
$sql_update_read = 'UPDATE messages SET viewed = 1 WHERE receiver_id = :user_id AND viewed = 0';
$stmt_update_read = $pdo->prepare($sql_update_read);
$stmt_update_read->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_update_read->execute();

// プレイリクエストの送信と受信を取得
$sql_requests_sent = 'SELECT r.*, u.username AS receiver_name, u.picture AS receiver_picture FROM match_requests r JOIN users_table u ON r.receiver_id = u.id WHERE r.sender_id = :user_id';
$stmt_requests_sent = $pdo->prepare($sql_requests_sent);
$stmt_requests_sent->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_requests_sent->execute();
$requests_sent = $stmt_requests_sent->fetchAll(PDO::FETCH_ASSOC);

$sql_requests_received = 'SELECT r.*, u.username AS sender_name, u.picture AS sender_picture, u.bio AS sender_bio, u.district AS sender_district, u.interests AS sender_interests FROM match_requests r JOIN users_table u ON r.sender_id = u.id WHERE r.receiver_id = :user_id';
$stmt_requests_received = $pdo->prepare($sql_requests_received);
$stmt_requests_received->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_requests_received->execute();
$requests_received = $stmt_requests_received->fetchAll(PDO::FETCH_ASSOC);

// メッセージをユーザーごとにまとめる
$messages_by_user = [];

foreach ($sent_messages as $message) {
    $receiver_id = $message['receiver_id'];
    if (!isset($messages_by_user[$receiver_id])) {
        $messages_by_user[$receiver_id] = [
            'user' => [
                'id' => $receiver_id,
                'name' => $message['receiver_name'],
                'picture' => $message['receiver_picture'],
                'bio' => $message['receiver_bio'],
                'district' => $message['receiver_district'],
                'interests' => $message['receiver_interests']
            ],
            'messages' => []
        ];
    }
    $messages_by_user[$receiver_id]['messages'][] = [
        'type' => 'sent',
        'message' => $message['message'],
        'created_at' => $message['created_at']
    ];
}

foreach ($received_messages as $message) {
    $sender_id = $message['sender_id'];
    if (!isset($messages_by_user[$sender_id])) {
        $messages_by_user[$sender_id] = [
            'user' => [
                'id' => $sender_id,
                'name' => $message['sender_name'],
                'picture' => $message['sender_picture'],
                'bio' => $message['sender_bio'],
                'district' => $message['sender_district'],
                'interests' => $message['sender_interests']
            ],
            'messages' => []
        ];
    }
    $messages_by_user[$sender_id]['messages'][] = [
        'type' => 'received',
        'message' => $message['message'],
        'created_at' => $message['created_at']
    ];
}

foreach ($messages_by_user as &$user_data) {
    usort($user_data['messages'], function ($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
}
unset($user_data);

function format_date($datetime)
{
    return date('m/d H:i', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メッセージ履歴</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }

        .message-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .message-text {
            margin-left: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s ease;
        }

        .modal-content {
            position: relative;
            margin: 10% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
            }

            to {
                transform: translateY(0);
            }
        }

        .message-group {
            margin-bottom: 20px;
        }

        .message-group-header {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .message-group-header img {
            margin-right: 10px;
        }

        .message-group-body {
            display: none;
            margin-top: 10px;
            margin-left: 20px;
        }

        .sent-message {
            text-align: right;
        }

        .received-message {
            text-align: left;
        }

        .badge {
            background-color: red;
            color: white;
            padding: 5px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .reply-form {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
        }

        .reply-form textarea {
            width: 100%;
            margin-bottom: 10px;
        }

        .reply-form button {
            align-self: flex-end;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            background-color: #fff;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .request {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .request img {
            margin-right: 10px;
        }

        .request span {
            flex-grow: 1;
        }

        .badge {
            margin-left: 10px;
        }

        .reply-form {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
        }

        .reply-form textarea {
            width: 100%;
            margin-bottom: 10px;
        }

        .reply-form button {
            align-self: flex-end;
        }

        .message-group {
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .message-group-header {
            background-color: #f7f7f7;
            padding: 10px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .message-group-header:hover {
            background-color: #e7e7e7;
        }

        .message-group-body {
            padding: 10px;
        }

        .message-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
        }

        .sent-message {
            background-color: #e1f5fe;
            align-self: flex-end;
            text-align: right;
        }

        .received-message {
            background-color: #fff8e1;
            align-self: flex-start;
            text-align: left;
        }

        .message-text {
            flex-grow: 1;
            padding: 10px;
            border-radius: 10px;
        }

        .message-time {
            margin-left: 10px;
            font-size: 0.8rem;
            color: #888;
            align-self: flex-end;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            margin-right: 10px;
        }

        .reply-form {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .reply-form textarea {
            width: 100%;
            margin-bottom: 10px;
            border-radius: 10px;
            padding: 10px;
        }

        .reply-form button {
            align-self: flex-end;
            padding: 5px 15px;
            border-radius: 10px;
        }

        .message-group {
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 50%;
            /* 幅の調整: 70%に変更 */
            margin-left: auto;
            /* 中央揃え */
            margin-right: auto;
            /* 中央揃え */
        }

        .play-requests-container {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            width: 50%;
            margin-left: auto;
            /* 中央揃え */
            margin-right: auto;
            /* 中央揃え */

        }

        .bo {
            width: 50%;
            margin-left: auto;
            /* 中央揃え */
            margin-right: auto;
            /* 中央揃え */
            display: flex;
            justify-content: center;
        }

        .message-group-body {
            padding: 10px;
        }

        .fixed-icon-menu {
            position: fixed;
            top: 30px;
            left: 200px;
            z-index: 1200;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: white;
            opacity: 0.9;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);

        }

        .icon {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
        }

        .icon-img {
            width: 100px;
            height: 100px;
            cursor: pointer;
        }

        .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: rgba(0, 0, 0, 0.7);
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 5px 0;
            position: absolute;
            z-index: 1;
            top: 100%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }

        .icon:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <!-- 固定されたアイコンとドロップダウンメニュー -->
    <div class="fixed-icon-menu">
        <div class="icon">
            <img class="icon-img" src="./Img/ie.png" alt="Search" onclick="window.location.href='./main.php'">
            <span class="tooltip-text">メイン画面へ</span>
        </div>
        <div class="icon">
            <img style="width: 100px;margin-top:8px;" class="menu" src="./Img/match.png" alt="Search" onclick="window.location.href='./match.php'">
            <span class="tooltip-text">実践</span>
        </div>
        <div class="icon">
            <img style="width: 135px;" class="menu" src="./Img/iine.png" alt="Calendar" onclick="window.location.href='./reservations.php'">
            <span class="tooltip-text">予約一覧</span>
        </div>

    </div>
    <div class="container mt-5">


        <div class="play-requests-container mb-5">

            <div class="requests-sent mb-3">
                <h3>送信中のリクエスト</h3>
                <?php if (count($requests_sent) === 0) : ?>
                    <p>Let'sPlayリクエストはありません。</p>
                <?php else : ?>
                    <?php foreach ($requests_sent as $request) : ?>
                        <div class="request card p-2 mb-2">
                            <img src="<?= htmlspecialchars($request['receiver_picture']) ?>" alt="Receiver Picture" class="profile-img">
                            <span><?= htmlspecialchars($request['receiver_name']) ?></span>
                            <?php if ($request['status'] === 'pending') : ?>
                                <span class="badge badge-info">送信中</span>
                            <?php elseif ($request['status'] === 'approved') : ?>
                                <span class="badge badge-success">承認されました</span>
                            <?php else : ?>
                                <span class="badge badge-danger">否認されました</span>
                            <?php endif; ?>
                            <form action="delete_request.php" method="post" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">削除</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="requests-received mb-3">
                <h3>受信したリクエスト</h3>

                <?php if (count($requests_received) === 0) : ?>
                    <p>Let'sPlayリクエストはありません。</p>
                <?php else : ?>
                    <?php foreach ($requests_received as $request) : ?>
                        <div class="request card p-2 mb-2">
                            <img src="<?= htmlspecialchars($request['sender_picture']) ?>" alt="Sender Picture" class="profile-img">
                            <span><?= htmlspecialchars($request['sender_name']) ?></span>
                            <?php if ($request['status'] === 'pending') : ?>
                                <form action="update_request.php" method="post" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                    <input type="hidden" name="action" value="approved">
                                    <input type="hidden" name="sender_picture" value="<?= htmlspecialchars($request['sender_picture']) ?>">
                                    <input type="hidden" name="sender_name" value="<?= htmlspecialchars($request['sender_name']) ?>">
                                    <input type="hidden" name="sender_bio" value="<?= htmlspecialchars($request['sender_bio']) ?>">
                                    <input type="hidden" name="sender_district" value="<?= htmlspecialchars($request['sender_district']) ?>">
                                    <input type="hidden" name="sender_interests" value="<?= htmlspecialchars($request['sender_interests']) ?>">
                                    <button type="submit" class="btn btn-success btn-sm">承認</button>
                                </form>
                                <form action="update_request.php" method="post" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                    <input type="hidden" name="action" value="denied">
                                    <button type="submit" class="btn btn-danger btn-sm">否認</button>
                                </form>
                            <?php else : ?>
                                <span class="badge badge-info"><?= htmlspecialchars($request['status']) === 'approved' ? '承認しました' : '否認しました' ?></span>
                                <?php if ($request['status'] === 'approved') : ?>
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='match.php?user_id=<?= $request['sender_id'] ?>'">予定を調整しましょう</button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <form action="delete_request.php" method="post" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">削除</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="">
            <?php foreach ($messages_by_user as $user_id => $user_data) : ?>
                <div class="message-group">
                    <div class="message-group-header" onclick="toggleMessages(<?= $user_id ?>)">
                        <?php if ($unread_count > 0) : ?>
                            <div class="badge"><?= $unread_count ?></div>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($user_data['user']['picture']) ?>" alt="Profile Picture" class="profile-img" onclick="openProfileModal('<?= htmlspecialchars($user_data['user']['name']) ?>', '<?= htmlspecialchars($user_data['user']['bio']) ?>', '<?= htmlspecialchars($user_data['user']['district']) ?>', '<?= htmlspecialchars($user_data['user']['interests']) ?>')">
                        <div class="message-text"><?= htmlspecialchars($user_data['user']['name']) ?></div>
                        <div id="toggle-icon-<?= $user_id ?>" class="ml-auto">▼</div>
                        <form action="send_request.php" method="post">
                            <input type="hidden" name="receiver_id" value="<?= $user_id ?>">
                            <button type="submit" class="btn btn-primary mt-2">Let's Play</button>
                        </form>
                    </div>
                    <div id="message-body-<?= $user_id ?>" class="message-group-body">
                        <?php foreach ($user_data['messages'] as $message) : ?>
                            <div class="message-row <?= $message['type'] === 'sent' ? 'sent-message' : 'received-message' ?>">
                                <div class="message-text"><?= htmlspecialchars($message['type'] === 'sent' ? 'あなた' : $user_data['user']['name']) ?>: <?= htmlspecialchars($message['message']) ?></div>
                                <div class="message-time"><?= format_date($message['created_at']) ?></div>
                            </div>
                        <?php endforeach; ?>
                        <div class="reply-form">
                            <form action="send_reply.php" method="post">
                                <input type="hidden" name="receiver_id" value="<?= $user_id ?>">
                                <textarea name="message" placeholder="返信メッセージを入力してください"></textarea>
                                <button type="submit" class="btn btn-primary">送信</button>
                            </form>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="bo">
                <a href="main.php" class="btn btn-primary mt-3">メイン画面に戻る</a>
            </div>
        </div>

        <!-- プロフィールモーダル -->
        <div id="profileModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeProfileModal()">&times;</span>
                <h2>プロフィール詳細</h2>
                <p><strong>名前:</strong> <span id="modal-username"></span></p>
                <p><strong>自己紹介:</strong> <span id="modal-bio"></span></p>
                <p><strong>お住まいの区:</strong> <span id="modal-district"></span></p>
                <p><strong>興味:</strong> <span id="modal-interests"></span></p>
            </div>
        </div>

        <script>
            function toggleMessages(userId) {
                const messageBody = document.getElementById(`message-body-${userId}`);
                const toggleIcon = document.getElementById(`toggle-icon-${userId}`);
                if (messageBody.style.display === 'none' || messageBody.style.display === '') {
                    messageBody.style.display = 'block';
                    toggleIcon.textContent = '▲';
                } else {
                    messageBody.style.display = 'none';
                    toggleIcon.textContent = '▼';
                }
            }

            function openProfileModal(name, bio, district, interests) {
                document.getElementById('modal-username').innerText = name;
                document.getElementById('modal-bio').innerText = bio;
                document.getElementById('modal-district').innerText = district;
                document.getElementById('modal-interests').innerText = interests;
                document.getElementById('profileModal').style.display = 'block';
            }

            function closeProfileModal() {
                document.getElementById('profileModal').style.display = 'none';
            }

            // モーダルの外側をクリックした時に閉じる処理
            window.onclick = function(event) {
                var modal = document.getElementById('profileModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
</body>

</html>