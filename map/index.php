<?php
// Генерация 100 домов с координатами и случайными проблемами + адресами
$houses = [];
$centerLat = 46.3497;
$centerLng = 48.0408;
$streets = ['Советская', 'Кирова', 'Пушкина', 'Ленина', 'Набережная', 'Чкалова'];

$totalHouses = 100;
$problemHouseRatio = 0.3;
$maxProblemHouses = round($totalHouses * $problemHouseRatio);
$currentProblemHouses = 0;

for ($i = 0; $i < $totalHouses; $i++) {
    $lat = $centerLat + (mt_rand(-500, 500) / 10000);
    $lng = $centerLng + (mt_rand(-500, 500) / 10000);

    $isProblematic = false;
    $issues = [
        'water' => false,
        'heating' => false,
        'electricity' => false,
        'sewage' => false
    ];

    if ($currentProblemHouses < $maxProblemHouses && mt_rand(1, 100) <= ($problemHouseRatio * 100)) {
        $issueTypes = array_keys($issues);
        $numIssues = mt_rand(1, 3);
        for ($j = 0; $j < $numIssues; $j++) {
            $type = $issueTypes[array_rand($issueTypes)];
            $issues[$type] = true;
            $isProblematic = true;
        }
        if ($isProblematic) $currentProblemHouses++;
    }

    $street = $streets[array_rand($streets)];
    $houseNum = mt_rand(1, 150);
    $address = "ул. $street, д. $houseNum";

    $house = [
        'id' => $i + 1,
        'lat' => $lat,
        'lng' => $lng,
        'address' => $address,
        'issues' => $issues,
        'telemetry' => [
            'water_temp' => mt_rand(30, 70) . ' °C',
            'water_pressure' => mt_rand(1, 5) . ' бар',
            'heat_temp' => mt_rand(40, 80) . ' °C',
            'heat_pressure' => mt_rand(1, 4) . ' бар',
            'voltage' => mt_rand(210, 250) . ' В',
            'humidity' => mt_rand(40, 100) . ' %'
        ]
    ];

    $houses[] = $house;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Цифровая карта ЖКХ</title>
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        #map { width: 100%; height: 100vh; }
        .layer-controls {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.3);
            font-family: sans-serif;
            z-index: 999;
        }
    </style>
</head>
<body>
<div class="layer-controls">
    <strong>Режим отображения:</strong><br>
    <label><input type="radio" name="mode" value="issues"> Только с проблемами</label><br>
    <label><input type="radio" name="mode" value="all" checked> Все дома</label>
    <hr>
    <strong>Фильтры по проблемам:</strong><br>
    <label><input type="checkbox" class="layer-toggle" value="water" checked> Вода</label><br>
    <label><input type="checkbox" class="layer-toggle" value="heating" checked> Отопление</label><br>
    <label><input type="checkbox" class="layer-toggle" value="electricity" checked> Электричество</label><br>
    <label><input type="checkbox" class="layer-toggle" value="sewage" checked> Канализация</label>
</div>

<div id="map"></div>

<script>
    const houseData = <?php echo json_encode($houses); ?>;
</script>
<script src="js/map.js"></script>
</body>
</html>