<?php
session_start();
include('functions.php');

$errmessage = array();
$pdo = connect_to_db();

if (!isset($_SESSION['username']) || !isset($_SESSION['mail']) || !isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$mail = $_SESSION['mail'];
$user_id = $_SESSION['id'];

$record = fetch_user_info($pdo, $username, $mail);

if (!$record) {
    die('ユーザー情報が見つかりませんでした。');
}

$profile_picture = $record['picture'] ?: './Img/defo.png';

$_SESSION['user_district'] = $record['district'];
$_SESSION['profile_picture'] = $profile_picture;

$start_time = isset($record['start_time']) ? $record['start_time'] : '00:00';
$end_time = isset($record['end_time']) ? $record['end_time'] : '00:00';

// プロフィール設定がされていない場合のフラグを設定
$profile_incomplete = empty($record['district']) || empty($record['interests']);

// 未読メッセージの数を取得
$sql = 'SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = :user_id AND viewed = 0';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$unread_count_result = $stmt->fetch(PDO::FETCH_ASSOC);
$unread_count = $unread_count_result['unread_count'];

// 新着メッセージを取得
$sql = 'SELECT m.*, u.username AS sender_name, u.picture AS sender_picture FROM messages m JOIN users_table u ON m.sender_id = u.id WHERE m.receiver_id = :user_id AND m.viewed = 0 ORDER BY m.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$new_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 承認されたリクエストを取得
$sql_approved_requests = 'SELECT r.*, u.username AS sender_name, u.picture AS sender_picture, u.bio AS sender_bio, u.district AS sender_district, u.interests AS sender_interests FROM match_requests r JOIN users_table u ON r.sender_id = u.id WHERE r.receiver_id = :user_id AND r.status = "approved"';
$stmt_approved_requests = $pdo->prepare($sql_approved_requests);
$stmt_approved_requests->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_approved_requests->execute();
$approved_requests = $stmt_approved_requests->fetchAll(PDO::FETCH_ASSOC);

if (!empty($approved_requests)) {
    $_SESSION['approved_requests'] = [];
    foreach ($approved_requests as $request) {
        $_SESSION['approved_requests'][] = [
            'sender_picture' => $request['sender_picture'],
            'sender_name' => $request['sender_name'],
            'sender_bio' => $request['sender_bio'],
            'sender_district' => $request['sender_district'],
            'sender_interests' => $request['sender_interests']
        ];
    }
}

// プロフィール更新処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $mail = $_POST['mail'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $district = $_POST['district'] ?? '';
    $interests = isset($_POST['interests']) ? json_encode($_POST['interests']) : '[]';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $days = isset($_POST['days']) ? json_encode($_POST['days']) : '[]';

    $picture = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['picture']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $errmessage[] = '許可されていないファイルタイプです。';
        } else {
            $picture = $uploadDir . basename($_FILES['picture']['name']);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
        }
    } else {
        $picture = $record['picture'];
    }

    if (empty($errmessage)) {
        $pdo = connect_to_db();
        $sql = 'UPDATE users_table SET username=:username, mail=:mail, birthday=:birthday, picture=:picture, bio=:bio, district=:district, interests=:interests, start_time=:start_time, end_time=:end_time, days=:days, updated_at=now() WHERE id=:id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $record['id'], PDO::PARAM_INT);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
        $stmt->bindValue(':birthday', $birthday, PDO::PARAM_STR);
        $stmt->bindValue(':picture', $picture, PDO::PARAM_STR);
        $stmt->bindValue(':bio', $bio, PDO::PARAM_STR);
        $stmt->bindValue(':district', $district, PDO::PARAM_STR);
        $stmt->bindValue(':interests', $interests, PDO::PARAM_STR);
        $stmt->bindValue(':start_time', $start_time, PDO::PARAM_STR);
        $stmt->bindValue(':end_time', $end_time, PDO::PARAM_STR);
        $stmt->bindValue(':days', $days, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['update_success'] = "プロフィールが更新されました！";
            $_SESSION['username'] = $username;
            $_SESSION['mail'] = $mail;
            $_SESSION['user_district'] = $district;
            $_SESSION['profile_picture'] = $picture;

            header('Location: main.php'); // 更新後にリダイレクト
            exit();
        } else {
            $_SESSION['update_error'] = "プロフィールの更新に失敗しました。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メイン画面</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="top-bar"></div> <!-- 上部の黒い帯 -->

    <div class="profile-container" id="profileIcon">
        <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture">
        <div class="welcome-text">ようこそ <span style="font-size:50px;color:yellow"><?= htmlspecialchars($username) ?></span> さん！</div>
    </div>

    <?php if (isset($_SESSION['update_success'])) : ?>
        <div id="profileUpdateMessage" class="message-alert show"><?= $_SESSION['update_success']; ?></div>
        <?php unset($_SESSION['update_success']); ?>
    <?php endif; ?>

    <?php if (!empty($new_messages)) : ?>
        <div id="newMessageAlert" class="new-message-alert show" onclick="location.href='mailbox.php'">
            <div class="message-sender">
                <img src="<?= htmlspecialchars($new_messages[0]['sender_picture']) ?>" alt="Sender Picture">
                <div>
                    <div><?= htmlspecialchars($new_messages[0]['sender_name']) ?></div>
                    <div class="message-text"><?= htmlspecialchars($new_messages[0]['message']) ?></div>
                </div>
            </div>
            <div class="message-time"><?= htmlspecialchars($new_messages[0]['created_at']) ?></div>
        </div>
    <?php else : ?>
        <div id="newMessageAlert" class="new-message-alert show" onclick="this.style.display='none'">
            新着メッセージはありません。
        </div>
    <?php endif; ?>

    <!-- プロフィール設定が未完了の場合のポップアップ -->
    <?php if ($profile_incomplete) : ?>
        <div id="profileIncompleteModal" class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="closeProfileIncompleteModal()">&times;</span>
                <h2>プロフィール設定</h2>
                <p>プロフィール設定がまだ完了していません。まずはプロフィールを設定しましょう！</p>
                <div class="yoko">
                    <button style="height:200px;margin-bottom:200px" onclick="openProfileModal()" class="btn btn-primary">プロフィール編集へ</button>
                    <img style="height:400px" src="./Img/nabiko.png" alt="">
                </div>
            </div>
        <?php endif; ?>

        <!-- ヘルプアイコン -->
        <div class="help-container">
            <img class="help-icon" src="./Img/info.png" alt="Help" onclick="openHelpModal()">
            <span class="tooltip-text2">初めての方はこちら！</span>
        </div>

        <!-- ヘルプモーダル -->
        <div id="helpModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeHelpModal()">&times;</span>
                <h2>アプリの使い方</h2>
                <h5>ここにアプリの使い方に関する情報を記載します。</h5>
                <img src="./Img/プロフィール.jpg" alt="">
                <h5>まずはプロフィール画面を設定しましょう！</h5>
                <img src="./Img/suku2.jpg" alt="">
                <h5>※設定画面をスキップしてしまった場合でもアイコンをクリックするとフォームが開きます。</h5>
                <img src="./Img/suku3.jpg" alt="">
                <h5>上記のように入力してみましょう。自己紹介にはテニス歴や基本いつテニスをしているかなど記載しておきましょう。
                </h5>
                <img src="./Img/suku10.jpg" alt="">
                <h5>アイコンの説明<br>
                    左順1つ目（虫眼鏡）：まずは一緒にテニスをしてくれる人を探しましょう！<br>
                    2つ目（ラケット）：これはまだ使用しません。のちにリクエストを送り承認された場合に使用します。<br>
                    3つ目（メッセージ）：1つ目のアイコンでやり取りする相手を決め、こちらでやり取りしましょう。<br>
                    4つ目（カレンダー）：2つ目のラケットで一緒にやる方が決まったら予定がここに表示されます。<br>
                    5つ目（設定）：設定画面です。クリックでドロップダウンが出てきます。<br>
                    まずは虫眼鏡アイコンで仲間を探しに行きましょう！</h5>
            </div>
        </div>

        <div class="icon-menu">
            <div class="icon-container">
                <img style="width: 135px;margin-left:20px;" class="menu" src="./Img/kensaku.png" alt="Search" onclick="window.location.href='./search.php'">
                <span class="tooltip-text">探す</span>
            </div>
            <div class="icon-container">
                <img style="width: 130px;margin-top:8px;" class="menu" src="./Img/match.png" alt="Search" onclick="window.location.href='./match.php'">
                <span class="tooltip-text">実践</span>
            </div>
            <div class="icon-container">
                <img style="width: 195px;margin-top:8px;" class="menu" src="./Img/message.png" alt="Messages" onclick="window.location.href='./mailbox.php'">
                <?php if ($unread_count > 0) : ?>
                    <div class="badge"><?= $unread_count ?></div>
                <?php endif; ?>
                <span class="tooltip-text">メッセージ</span>
            </div>
            <div class="icon-container">
                <img style="width: 135px;" class="menu" src="./Img/iine.png" alt="Calendar" onclick="window.location.href='./reservations.php'">
                <span class="tooltip-text">予約一覧</span>
            </div>
            <div class="icon-container" id="settingsIcon">
                <img style="width: 135px;" class="menu" src="./Img/set3.png" alt="Settings" onclick="toggleDropdown()">
                <span class="tooltip-text">設定</span>
                <div id="dropdownMenu" class="dropdown-content">
                    <a href="#" onclick="openProfileModal()">プロフィールを更新</a>
                    <a href="changepass.php">パスワードを更新</a>
                    <a href="register.php">再新規登録</a>
                    <a href="logout.php">ログアウト</a>
                </div>
            </div>
        </div>

        <div class="bottom-bar"></div> <!-- 下部の黒い帯 -->

        <!-- モーダル（ポップアップ） -->
        <div id="profileModal" class="modal">
            <div class="modal-content" style="width: 60%;">
                <span class="close" onclick="closeProfileModal()">&times;</span>
                <div class="container mt-5">
                    <h1 class="mb-3">プロフィール編集</h1>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="picture" class="form-label">プロフィール写真：</label>
                                    <input type="file" class="form-control" id="picture" name="picture" onchange="previewNewImage(event)">
                                    <?php if (!empty($record['picture'])) : ?>
                                        <div style='display:flex;'>
                                            <div>now:<br>
                                                <img src="<?= htmlspecialchars($record['picture']) ?>" alt="Current Profile Picture" class="mt-2" style="max-width: 150px;">
                                            </div>
                                            <div>new:<br>
                                                <img id="newProfilePicture" alt="New Profile Picture" class="mt-2" style="max-width: 150px; display: none;">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">名前：</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($record['username']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="mail" class="form-label">メールアドレス：</label>
                                    <input type="email" class="form-control" id="mail" name="mail" value="<?= htmlspecialchars($record['mail']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="birthday" class="form-label">誕生日：</label>
                                    <input type="date" class="form-control" id="birthday" name="birthday" value="<?= htmlspecialchars($record['birthday']) ?>" onchange="updateAge()">
                                    <span id="age" style="font-size: 1.5rem; font-weight: bold;"></span>
                                </div>

                                <div class="mb-3">
                                    <label for="district" class="form-label">お住まいの市、区：</label>
                                    <select class="form-control" id="district" name="district">
                                        <option value="" <?= $record['district'] == '' ? 'selected' : '' ?>>選択してください</option>
                                        <option value="博多区" <?= $record['district'] == '博多区' ? 'selected' : '' ?>>博多区</option>
                                        <option value="中央区" <?= $record['district'] == '中央区' ? 'selected' : '' ?>>中央区</option>
                                        <option value="東区" <?= $record['district'] == '東区' ? 'selected' : '' ?>>東区</option>
                                        <option value="南区" <?= $record['district'] == '南区' ? 'selected' : '' ?>>南区</option>
                                        <option value="西区" <?= $record['district'] == '西区' ? 'selected' : '' ?>>西区</option>
                                        <option value="城南区" <?= $record['district'] == '城南区' ? 'selected' : '' ?>>城南区</option>
                                        <option value="早良区" <?= $record['district'] == '早良区' ? 'selected' : '' ?>>早良区</option>
                                        <option value="太宰府市" <?= $record['district'] == '太宰府市' ? 'selected' : '' ?>>太宰府市</option>
                                        <option value="糟屋郡" <?= $record['district'] == '糟屋郡' ? 'selected' : '' ?>>糟屋郡</option>
                                        <option value="那珂川市" <?= $record['district'] == '那珂川市' ? 'selected' : '' ?>>那珂川市</option>
                                        <option value="古賀市" <?= $record['district'] == '古賀市' ? 'selected' : '' ?>>古賀市</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">これ参加！：</label>
                                    <button type="button" class="btn btn-primary btn-sm" id="toggleInterests" onclick="toggleSelectAll('interests')">全選択</button>
                                    <?php
                                    $interests = json_decode($record['interests'], true);
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input interests" type="checkbox" id="interest1" name="interests[]" value="テニス" <?= in_array('テニス', $interests) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="interest1">テニス</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input interests" type="checkbox" id="interest2" name="interests[]" value="ソフトテニス" <?= in_array('ソフトテニス', $interests) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="interest2">ソフトテニス</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input interests" type="checkbox" id="interest3" name="interests[]" value="壁打ちお供" <?= in_array('壁打ちお供', $interests) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="interest3">壁打ちお供</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="activity_time" class="form-label">基本活動時間：</label>
                                    <div class="d-flex align-items-center">
                                        <select class="form-control" id="start_time" name="start_time">
                                            <?php for ($h = 0; $h < 24; $h++) : ?>
                                                <option value="<?= sprintf('%02d:00', $h) ?>" <?= (substr($start_time, 0, 5) == sprintf('%02d:00', $h)) ? 'selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
                                                <option value="<?= sprintf('%02d:30', $h) ?>" <?= (substr($start_time, 0, 5) == sprintf('%02d:30', $h)) ? 'selected' : '' ?>><?= sprintf('%02d:30', $h) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <span class="mx-2">～</span>
                                        <select class="form-control" id="end_time" name="end_time">
                                            <?php for ($h = 0; $h < 24; $h++) : ?>
                                                <option value="<?= sprintf('%02d:00', $h) ?>" <?= (substr($end_time, 0, 5) == sprintf('%02d:00', $h)) ? 'selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
                                                <option value="<?= sprintf('%02d:30', $h) ?>" <?= (substr($end_time, 0, 5) == sprintf('%02d:30', $h)) ? 'selected' : '' ?>><?= sprintf('%02d:30', $h) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">自己紹介：</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="よろしくお願いします！"><?= htmlspecialchars($record['bio']) ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="margin-left:40%;font-size:25px">更新</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function toggleSelectAll(className) {
                const checkboxes = document.querySelectorAll(`.${className}`);
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                checkboxes.forEach(checkbox => checkbox.checked = !allChecked);

                // ボタンのテキストを変更
                const toggleButton = document.getElementById(`toggle${className.charAt(0).toUpperCase() + className.slice(1)}`);
                toggleButton.textContent = allChecked ? '全選択' : '全解除';
            }

            function toggleDropdown() {
                var dropdown = document.getElementById('dropdownMenu');
                if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                    dropdown.style.display = 'block';
                } else {
                    dropdown.style.display = 'none';
                }
            }

            function openProfileIncompleteModal() {
                document.getElementById('profileIncompleteModal').style.display = 'block';
            }

            function closeProfileIncompleteModal() {
                document.getElementById('profileIncompleteModal').style.display = 'none';
            }

            function openProfileModal() {
                document.getElementById('profileModal').style.display = 'block';
            }

            function closeProfileModal() {
                document.getElementById('profileModal').style.display = 'none';
            }

            function openHelpModal() {
                document.getElementById('helpModal').style.display = 'block';
            }

            function closeHelpModal() {
                document.getElementById('helpModal').style.display = 'none';
            }

            function previewNewImage(event) {
                const reader = new FileReader();
                reader.onload = function() {
                    const output = document.getElementById('newProfilePicture');
                    output.src = reader.result;
                    output.style.display = 'block';
                };
                reader.readAsDataURL(event.target.files[0]);
            }

            // ドロップダウンメニューをクリック以外で閉じる
            window.onclick = function(event) {
                var helpModal = document.getElementById('helpModal');
                if (event.target == helpModal) {
                    helpModal.style.display = 'none';
                }

                var profileModal = document.getElementById('profileModal');
                if (event.target == profileModal) {
                    profileModal.style.display = 'none';
                }

                var profileIncompleteModal = document.getElementById('profileIncompleteModal');
                if (event.target == profileIncompleteModal) {
                    profileIncompleteModal.style.display = 'none';
                }

                var dropdown = document.getElementById('dropdownMenu');
                var settingsIcon = document.getElementById('settingsIcon');
                if (event.target !== settingsIcon && !settingsIcon.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            }

            window.onload = function() {
                const profileIcon = document.getElementById("profileIcon");
                const modal = document.getElementById("profileModal");
                const modal2 = document.getElementById("helpModal");

                const closeBtn = document.querySelectorAll(".close");

                profileIcon.onclick = function() {
                    modal.style.display = "block";
                }

                closeBtn.forEach(function(btn) {
                    btn.onclick = function() {
                        modal.style.display = "none";
                        modal2.style.display = "none";
                    }
                });

                // 誕生日が設定されている場合、年齢を更新
                if (document.getElementById('birthday').value) {
                    updateAge();
                }

                // プロフィール更新メッセージを表示
                const updateMessage = document.getElementById('updateMessage');
                if (updateMessage) {
                    updateMessage.classList.add('show');
                    setTimeout(() => {
                        updateMessage.classList.remove('show');
                    }, 1000); // 3秒後にメッセージをフェードアウト
                }
            }

            function updateAge() {
                var birthday = new Date(document.getElementById('birthday').value);
                var today = new Date();
                var age = today.getFullYear() - birthday.getFullYear();
                var m = today.getMonth() - birthday.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthday.getDate())) {
                    age--;
                }
                document.getElementById('age').textContent = age + '歳';
            }
        </script>
</body>

</html>