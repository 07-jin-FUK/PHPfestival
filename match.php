<?php
session_start();

if (!isset($_SESSION['approved_requests'])) {
    header('Location: main.php');
    exit();
}

include('functions.php');
$pdo = connect_to_db();

$requests = $_SESSION['approved_requests'];
$current_user = [
    'picture' => $_SESSION['profile_picture'],
    'district' => $_SESSION['user_district']
];

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

// 現在ユーザーの区の座標を取得
$current_user_location = $districts[$current_user['district']];
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マッチング成功</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }

        .profile-details {
            margin-left: 20px;
        }

        .tags {
            list-style: none;
            padding: 0;
        }

        .tags li {
            display: inline-block;
            background-color: #e7e7e7;
            border-radius: 3px;
            padding: 5px 10px;
            margin: 2px;
        }

        #map {
            height: 500px;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
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

        .modal-close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-close:hover,
        .modal-close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .fixed-icon-menu {
            position: fixed;
            top: 30px;
            left: 20px;
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
    <!-- Google Maps APIのスクリプトを追加 -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places" async defer></script>
</head>

<body onload="initMap()">
    <div class="fixed-icon-menu">
        <div class="icon">
            <img class="icon-img" src="./Img/ie.png" alt="Search" onclick="window.location.href='./main.php'">
            <span class="tooltip-text">メイン画面へ</span>
        </div>
        <div class="icon">
            <img style='width:150px' class="icon-img" src="./Img/message.png" alt="message" onclick="window.location.href='./mailbox.php'">
            <span class="tooltip-text">メッセージ画面へ</span>
        </div>

    </div>
    <div class="container mt-5">
        <h1 class="mb-3">コートを検索しよう！</h1>
        <div id="map"></div>
        <h1>現在のLet’s Play承認ユーザー</h1>
        <?php foreach ($requests as $request) : ?>
            <div class="profile-info d-flex mb-4">
                <img src="<?= htmlspecialchars($request['sender_picture']) ?>" alt="Profile Picture" class="profile-img">
                <div class="profile-details">
                    <h2><?= htmlspecialchars($request['sender_name']) ?></h2>
                    <p><?= htmlspecialchars($request['sender_bio']) ?></p>
                    <p>お住まいの区: <?= htmlspecialchars($request['sender_district']) ?></p>
                    <p>興味:
                    <ul class="tags">
                        <?php
                        $interests = json_decode($request['sender_interests'], true);
                        if (is_array($interests)) {
                            foreach ($interests as $interest) {
                                echo '<li>' . htmlspecialchars($interest) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
        <a href="main.php" class="btn btn-primary mt-3">メイン画面に戻る</a>
    </div>

    <!-- 予約フォームモーダル -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeBookingModal()">&times;</span>
            <h2>スケジュール</h2>
            <form id="bookingForm" method="POST" action="booking.php">
                <input type="hidden" name="location" id="bookingLocation">
                <div class="mb-3">
                    <label for="user">入力者:</label>
                    <input type="text" class="form-control" id="user" name="user" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="partner">お相手:</label>
                    <select class="form-control" id="partner" name="partner">
                        <?php foreach ($requests as $request) : ?>
                            <option value="<?= htmlspecialchars($request['sender_name']) ?>"><?= htmlspecialchars($request['sender_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">日時:</label>
                    <input type="datetime-local" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="notes">予約の連絡（Who）備考:</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">予約する</button>
            </form>
        </div>
    </div>
    <script>
        function initMap() {
            var currentUserLocation = {
                lat: <?= $current_user_location['lat'] ?>,
                lng: <?= $current_user_location['lng'] ?>
            };

            var map = new google.maps.Map(document.getElementById('map'), {
                center: currentUserLocation,
                zoom: 12
            });

            // 現在ユーザーのマーカー
            var currentUserMarker = new google.maps.Marker({
                position: currentUserLocation,
                map: map,
                title: '現在の位置',
                icon: {
                    url: '<?= $current_user['picture'] ?>',
                    scaledSize: new google.maps.Size(50, 50)
                }
            });

            // マッチしたユーザーのマーカー
            var markerPositions = {};

            <?php foreach ($requests as $request) :
                $matched_user_location = $districts[$request['sender_district']];
            ?>
                var matchedUserLocation = {
                    lat: <?= $matched_user_location['lat'] ?>,
                    lng: <?= $matched_user_location['lng'] ?>
                };

                // 座標のずらし量
                var offset = 0.01 * Object.keys(markerPositions).length;

                // もし同じ位置に既にマーカーがあれば少しずらす
                if (markerPositions[matchedUserLocation.lat] && markerPositions[matchedUserLocation.lat][matchedUserLocation.lng]) {
                    matchedUserLocation.lat += offset;
                    matchedUserLocation.lng += offset;
                }

                // マーカー位置を記録
                if (!markerPositions[matchedUserLocation.lat]) {
                    markerPositions[matchedUserLocation.lat] = {};
                }
                markerPositions[matchedUserLocation.lat][matchedUserLocation.lng] = true;

                var matchedUserMarker = new google.maps.Marker({
                    position: matchedUserLocation,
                    map: map,
                    title: '<?= $request['sender_name'] ?>',
                    icon: {
                        url: '<?= $request['sender_picture'] ?>',
                        scaledSize: new google.maps.Size(50, 50)
                    }
                });
            <?php endforeach; ?>

            // テニスコートのピンを追加
            var service = new google.maps.places.PlacesService(map);
            var request = {
                location: currentUserLocation,
                radius: 10000,
                keyword: 'tennis court'
            };

            service.nearbySearch(request, function(results, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    for (var i = 0; i < results.length; i++) {
                        createMarker(results[i]);
                    }
                }
            });

            function createMarker(place) {
                var placeLoc = place.geometry.location;
                var marker = new google.maps.Marker({
                    map: map,
                    position: placeLoc,
                    title: place.name
                });

                google.maps.event.addListener(marker, 'click', function() {
                    var photos = place.photos && place.photos.length > 0 ? place.photos[0].getUrl({
                        maxWidth: 200,
                        maxHeight: 200
                    }) : 'No photo available';
                    var url = place.url ? place.url : 'No URL available';
                    var content = `
                        <h3>${place.name}</h3>
                        <p>${place.vicinity}</p>
                        <p><img src="${photos}" alt="${place.name}"></p>
                        <p><a href="${url}" target="_blank">詳細を見る</a></p>
                        <div>
                            <button class="btn btn-primary" onclick="selectLocation('${place.name}', '${place.vicinity}')">この場所にする</button>
                        </div>
                    `;

                    var infowindow = new google.maps.InfoWindow({
                        content: content
                    });

                    infowindow.open(map, this);
                });
            }
        }

        function selectLocation(name, vicinity) {
            document.getElementById('bookingLocation').value = `${name}, ${vicinity}`;
            document.getElementById('bookingModal').style.display = 'block';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
    </script>
</body>

</html>