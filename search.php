<?php
session_start();
include('functions.php');

$pdo = connect_to_db();

if (!isset($_SESSION['username']) || !isset($_SESSION['mail'])) {
    header('Location: login.php');
    exit();
}

// メッセージ送信成功フラグと詳細情報をセッションから取得
$message_sent = false;

if (isset($_SESSION['last_message_sent']) && $_SESSION['last_message_sent'] === true) {
    $message_sent = true;
    unset($_SESSION['last_message_sent']);
}


$username = $_SESSION['username'];
$mail = $_SESSION['mail'];

$user_info = fetch_user_info($pdo, $username, $mail);
$user_district = $user_info['district'];
$user_interests = json_decode($user_info['interests'], true);

$districts = [
    '博多区' => ['lat' => 33.592, 'lng' => 130.412],
    '中央区' => ['lat' => 33.589, 'lng' => 130.392],
    '東区' => ['lat' => 33.609, 'lng' => 130.441],
    '南区' => ['lat' => 33.559, 'lng' => 130.412],
    '西区' => ['lat' => 33.578, 'lng' => 130.308],
    '城南区' => ['lat' => 33.561, 'lng' => 130.354],
    '早良区' => ['lat' => 33.539, 'lng' => 130.363],
    '太宰府市' => ['lat' => 33.511, 'lng' => 130.523],
    '糟屋郡' => ['lat' => 33.661, 'lng' => 130.480],
    '那珂川市' => ['lat' => 33.505, 'lng' => 130.415],
    '古賀市' => ['lat' => 33.728, 'lng' => 130.469]
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_districts = $_POST['districts'] ?? [];
    $selected_interests = $_POST['interests'] ?? [];

    $query = "SELECT * FROM users_table WHERE district IN (" . implode(", ", array_fill(0, count($selected_districts), '?')) . ")";
    $params = $selected_districts;
    if (!empty($selected_interests)) {
        $interest_conditions = [];
        foreach ($selected_interests as $interest) {
            $interest_conditions[] = "JSON_CONTAINS(interests, ?)";
            $params[] = json_encode($interest);
        }
        $query .= " AND (" . implode(" OR ", $interest_conditions) . ")";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $results = [];
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー検索</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url(./Img/ko-tono.jpg);
            background-size: cover;
            background-attachment: fixed;
            padding: 0;
        }

        .form-container {
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 1000px;
            margin: 20px auto;
        }

        #map {
            height: 68vh;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            margin: 20px auto;
            max-width: 1400px;
        }

        .profile-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .profile-modal-content {
            position: relative;
            margin: 10% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            background-color: white;
            border-radius: 10px;
        }

        .profile-modal-close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .profile-modal-close:hover,
        .profile-modal-close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        form {
            display: flex;
        }

        .ku {
            width: 420px;
            font-size: 20px;
        }

        .bunya {
            width: 350px;
            font-size: 20px;
        }

        .go {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        #to {
            width: 150px;
            height: 150px;
            font-size: 50px;
        }

        .legend-container {
            display: flex;
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
            justify-content: center;
            align-items: center;

        }

        .legend-container img {
            vertical-align: middle;
            margin-left: 20px;
            margin-right: 10px;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-size: 20px;
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 1000px;
            margin-top: 2%;
        }

        /* メッセージポップアップのスタイル */
        .message-popup {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 75%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            padding: 20px;
            width: 80%;
            max-width: 500px;
        }

        .message-popup-close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .message-popup-close:hover,
        .message-popup-close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        /* ここに既存のスタイルが続きます... */
        .message-sent-popup {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: gray;
            color: white;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            padding: 20px;
            width: 80%;
            max-width: 500px;
            text-align: center;
            font-size: 25px;
            opacity: 1;
            transition: opacity 1s ease-in-out;
        }

        .message-sent-popup.hide {
            opacity: 0;
        }

        .fixed-icon-menu {
            position: fixed;
            top: 30px;
            left: 60px;
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

        .dropdown-content {
            display: none;
            position: absolute;
            left: 60px;
            top: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            z-index: 1200;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #ddd;
        }

        .icon:hover .dropdown-content {
            display: block;
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
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease;
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</head>

<body>

    <div class="form-container">
        <form method="POST">
            <div class="ku">
                <label for="districts"><span style="font-size:40px;font-weight:bold;">STEP1</span><br>検索したい市・区を選択してください：<br>（複数選択可能）</label><br>
                <button class="btn btn-primary" type="button" onclick="toggleSelectAll('districts')">全選択/解除</button><br>
                <?php foreach ($districts as $district => $coords) : ?>
                    <input type="checkbox" name="districts[]" value="<?= htmlspecialchars($district) ?>" <?= in_array($district, $selected_districts ?? []) ? 'checked' : '' ?>> <?= htmlspecialchars($district) ?><br>
                <?php endforeach; ?>
            </div>
            <div class="bunya">
                <label for="interests"><span style="font-size:40px;font-weight:bold;">STEP2</span><br>興味のある分野を選択してください：</label><br>
                <button class="btn btn-primary" type="button" onclick="toggleSelectAll('interests')">全選択/解除</button><br>
                <?php
                $all_interests = ['テニス', 'ソフトテニス', '壁打ちお供'];
                foreach ($all_interests as $interest) : ?>
                    <input type="checkbox" name="interests[]" value="<?= htmlspecialchars($interest) ?>" <?= in_array($interest, $selected_interests ?? []) ? 'checked' : '' ?>> <?= htmlspecialchars($interest) ?><br>
                <?php endforeach; ?>
            </div>
            <div class="go">
                <button id="to" type="submit" class="btn btn-primary">検索</button>
            </div>
        </form>
    </div>

    <div class="container">
        <div class="legend-container">
            <p>
                <img src="<?= htmlspecialchars($user_info['picture']) ?>" alt="Your Profile Picture" style="width:50px; height:50px; border-radius:50%;">: あなたの現在地
            </p>
            <p>
                <img src="./Img/soro.png" alt="3 or fewer users" style="width:30px; height:30px;">: 3人以下のユーザー登録情報
            </p>
            <p>
                <img src="./Img/many.png" alt="4 or more users" style="width:30px; height:30px;">: 4人以上のユーザー登録情報
            </p>
        </div>
        <div>
            <p>※マークの所在地はプライバシーの問題上各区の中心部を示しております。</p>
        </div>
    </div>
    <div id="map"></div>


    <div id="profileModal" class="profile-modal">
        <div class="profile-modal-content">
            <span class="profile-modal-close" id="profileModalClose">&times;</span>
            <div id="profileDetails"></div>
        </div>
    </div>

    <!-- メッセージ送信ポップアップ -->
    <div id="messagePopup" class="message-popup">
        <span class="message-popup-close" id="messagePopupClose">&times;</span>
        <div id="messageFormPopup">
            <!-- メッセージフォームはここに動的に追加されます -->
        </div>
    </div>

    <!-- メッセージ送信成功ポップアップ -->
    <div id="messageSentPopup" class="message-sent-popup">
        <p>メッセージが送信されました！</p>
    </div>

    <!-- 固定されたアイコンとドロップダウンメニュー -->
    <div class="fixed-icon-menu">
        <div class="icon">
            <img class="icon-img" src="./Img/ie.png" alt="Search" onclick="window.location.href='./main.php'">
            <span class="tooltip-text">メイン画面へ</span>
        </div>
        <div class="icon">
            <img style='width:150px' class="icon-img" src="./Img/message.png" alt="message" onclick="window.location.href='./mailbox.php'">
            <span class="tooltip-text">メッセージ画面へ</span>
        </div>
        <div class="icon">
            <img style='width:90px;height:90px;' class="icon-img" src="./Img/info.png" alt="info" onclick="openHelpModal()">
            <span class="tooltip-text">使い方説明</span>
        </div>
    </div>

    <div id="helpModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeHelpModal()">&times;</span>
            <h2>アプリの使い方</h2>
            <h5>ここにアプリの使い方に関する情報を記載します。</h5>
            <img src="./Img/suku5.jpg" alt="">
            <h5>あなたがテニスをしたい人が住んでいる場所を選択してみましょう。<br>
                ※自身の住んでいる地域に近い場所を検索することをお勧めします。<br>
                STEP2ではあなたがほかの方と共にしたい種目を選択します。<br>
                いづれも複数選択可能です。</h5>
            <img src="./Img/suku6.jpg" alt="">
            <h5>マップ上に検索結果が表示されます。ユーザー登録表示されているアイコンをクリックしましょう。
            </h5>
            <img src="./Img/suku7.jpg" alt="">
            <h5>詳しく見る画面を押しましょう。
            </h5>
            <img src="./Img/suku8.jpg" alt="">
            <h5>登録されているユーザーのプロフィールが見られます。気になる方がいたらメッセージを送りましょう。
            </h5>
            <img src="./Img/suku9.jpg" alt="">
            <h5>アイコンの説明<br>
                上から1つ目（家）：先ほどのメイン画面に戻ります。<br>
                2つ目（メッセージ）：送ったメッセージを確認したり、あなたに送られてくるメッセージを確認します。<br>
                3つ目（インフォメーション）：使い方説明です。<br>
            </h5>
        </div>
    </div>

    <script>
        let map;
        const userDistrict = <?= json_encode($user_district) ?>;
        const userPicture = <?= json_encode($user_info['picture']) ?>;
        const userLatLng = {
            lat: <?= $districts[$user_district]['lat'] ?>,
            lng: <?= $districts[$user_district]['lng'] ?> - 0.02 // 自分の位置を少し左にずらす
        };
        const results = <?= json_encode($results) ?>;
        const districts = <?= json_encode($districts) ?>;
        const userInfo = <?= json_encode($user_info) ?>;
        let markers = {}; // グローバルスコープでmarkersを定義
        function toggleSelectAll(groupName) {
            const checkboxes = document.querySelectorAll(`input[name="${groupName}[]"]`);
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            checkboxes.forEach(checkbox => checkbox.checked = !allChecked);
        }

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12, // ズームレベルを12に設定
                center: {
                    lat: 33.5902,
                    lng: 130.4017
                }
            });

            // 自分の位置をアイコンで表示
            const userMarker = new google.maps.Marker({
                position: userLatLng,
                map: map,
                icon: {
                    url: userPicture,
                    scaledSize: new google.maps.Size(50, 50)
                }
            });

            // 検索結果のマーカーを表示
            results.forEach(result => {
                const district = result.district;
                if (!markers[district]) {
                    markers[district] = {
                        count: 0,
                        marker: new google.maps.Marker({
                            position: districts[district],
                            map: map,
                            icon: {
                                url: './Img/soro.png', // PNG画像のURLを設定
                                scaledSize: new google.maps.Size(50, 50) // 初期サイズを設定
                            }
                        }),
                        users: []
                    };
                    markers[district].marker.addListener('click', function() {
                        const content = `ユーザー登録数: ${markers[district].count}<br><button class="btn btn-primary" onclick="showDetails('${district}')">詳しく見る</button>`;
                        const infoWindow = new google.maps.InfoWindow({
                            content: content
                        });
                        infoWindow.open(map, markers[district].marker);
                    });
                }
                markers[district].count++;
                markers[district].users.push(result);

                // 3人以上の場合の画像変更
                if (markers[district].count >= 3) {
                    markers[district].marker.setIcon({
                        url: './Img/many.png', // 3人以上のユーザーがいる場合の画像
                        scaledSize: new google.maps.Size(50, 50) // サイズ調整
                    });
                }
            });

            // 点滅の設定
            setInterval(() => {
                for (const key in markers) {
                    markers[key].marker.setVisible(!markers[key].marker.getVisible());
                }
                userMarker.setVisible(!userMarker.getVisible());
            }, 500);
        }

        function showMessageForm(user) {
            document.getElementById('receiver_id').value = user.id;
            document.getElementById('messageForm').style.display = 'block';
        }

        // メッセージ送信成功のポップアップを表示し、1秒後にフェードアウト
        const messageSent = <?= json_encode($message_sent) ?>;
        if (messageSent) {
            const messageSentPopup = document.getElementById('messageSentPopup');
            messageSentPopup.style.display = 'block';
            setTimeout(() => {
                messageSentPopup.classList.add('hide');
                setTimeout(() => {
                    messageSentPopup.style.display = 'none';
                }, 1000); // フェードアウトにかかる時間と同じ
            }, 1000); // 1秒後にフェードアウト開始
        }

        function showDetails(district) {
            let details = '';
            const users = markers[district]?.users || [];
            users.forEach(user => {
                // interests を JSON デコードする
                let interests = [];
                try {
                    interests = JSON.parse(user.interests);
                } catch (e) {
                    console.error('Invalid JSON:', user.interests);
                }

                details += `<p>名前: ${user.username}</p>
                  
                    <p>プロフィール写真: <img src="${user.picture}" alt="Profile Picture" style="max-width: 150px;"></p>
                    <p>自己紹介: ${user.bio}</p>
                    <p>誕生日: ${user.birthday}</p>
                    <p>年齢: ${getAge(user.birthday)}</p>
                    <p>基本活動時間: ${user.start_time} - ${user.end_time}</p>
                    <p>興味: ${interests.join(', ')}</p>
                    <button class="btn btn-primary" onclick="showMessagePopup(${user.id}, '${user.username}')">この人にメッセージを送る</button>
                    <hr>`;
            });
            document.getElementById('profileDetails').innerHTML = details;
            document.getElementById('profileModal').style.display = 'block';
        }

        function getAge(birthday) {
            const birthDate = new Date(birthday);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age;
        }

        function showMessagePopup(userId, username) {
            const messageFormPopup = document.getElementById('messageFormPopup');
            messageFormPopup.innerHTML = `
                <h3>${username} にメッセージを送る</h3>
                <form method="POST" action="send_message.php">
                    <input type="hidden" name="receiver_id" value="${userId}">
                    <textarea name="message" required></textarea>
                    <button class="btn btn-primary" type="submit">送信</button>
                </form>
            `;
            document.getElementById('messagePopup').style.display = 'block';
        }

        document.getElementById('messagePopupClose').onclick = function() {
            document.getElementById('messagePopup').style.display = 'none';
        };

        window.onclick = function(event) {
            if (event.target == document.getElementById('messagePopup')) {
                document.getElementById('messagePopup').style.display = 'none';
            }
            if (event.target == document.getElementById('profileModal')) {
                document.getElementById('profileModal').style.display = 'none';
            }
        };

        document.getElementById('profileModalClose').onclick = function() {
            document.getElementById('profileModal').style.display = 'none';
        };

        function openHelpModal() {
            document.getElementById('helpModal').style.display = 'block';
        }

        function closeHelpModal() {
            document.getElementById('helpModal').style.display = 'none';
        }
        window.onclick = function(event) {
            var helpModal = document.getElementById('helpModal');
            if (event.target == helpModal) {
                helpModal.style.display = 'none';
            }
        }
    </script>
</body>

</html>