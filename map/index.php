<?php
// Функция генерации отдельного параметра по идеалу, юниту и статусу
// status может быть: 'good', 'deviation' или 'emergency'
function generateParam($ideal, $unit, $status) {
    if ($status === 'good') {
        // Хорошие параметры: отклонение в пределах ±5%
        $delta = mt_rand(-500, 500) / 10000; // от -0.05 до 0.05
    } elseif ($status === 'deviation') {
        // Отклонение: от 6% до 14%, случайный знак.
        $delta = mt_rand(600, 1400) / 10000;
        $delta = (mt_rand(0, 1) ? $delta : -$delta);
    } else { // emergency
        // Аварийные параметры: от 16% до 30%, случайный знак.
        $delta = mt_rand(1600, 3000) / 10000;
        $delta = (mt_rand(0, 1) ? $delta : -$delta);
    }
    $value = $ideal * (1 + $delta);
    $value = round($value, 1);
    return $value . ' ' . $unit;
}

// Параметры телеметрии: ключ => [идеальное значение, единица измерения]
$telemetryParams = [
    'water_temp'     => [50, '°C'],
    'water_pressure' => [3, 'бар'],
    'heat_temp'      => [60, '°C'],
    'heat_pressure'  => [2.5, 'бар'],
    'voltage'        => [230, 'В'],
    'humidity'       => [70, '%']
];

// Генерация 100 домов с координатами и случайными проблемами + адресами
$houses = [];
$centerLat = 46.3497;
$centerLng = 48.0408;
$streets = ['Советская', 'Кирова', 'Пушкина', 'Ленина', 'Набережная', 'Чкалова'];

$totalHouses = 100;

for ($i = 0; $i < $totalHouses; $i++) {
    $lat = $centerLat + (mt_rand(-500, 500) / 10000);
    $lng = $centerLng + (mt_rand(-500, 500) / 10000);

    // Генерация телеметрии с учетом того, что не более 2 параметров могут быть ненормальными.
    // 50% домов – полностью "good"
    // Остальные 50% – дом с 1 или 2 ненормальными параметрами.
    $telemetry = [];
    if (mt_rand(1, 100) <= 50) {
        // Дом с хорошими параметрами: все параметры генерируются по статусу 'good'
        foreach ($telemetryParams as $key => $param) {
            list($ideal, $unit) = $param;
            $telemetry[$key] = generateParam($ideal, $unit, 'good');
        }
    } else {
        // Аномальный дом: выбираем 1 или 2 параметра для аномалии
        $abnormalCount = mt_rand(1, 2);
        // Из оставшихся домов примерно 2/3 будут с отклонениями, 1/3 – аварийными.
        $randTemp = mt_rand(1, 100);
        $abnormalType = ($randTemp <= 67) ? 'deviation' : 'emergency';
        // Подготовим список ключей параметров для выбора
        $paramKeys = array_keys($telemetryParams);
        // Выбираем случайные ключи для аномальных параметров (без повторений)
        if ($abnormalCount === 1) {
            $abnormalKeys = [ $paramKeys[array_rand($paramKeys)] ];
        } else {
            shuffle($paramKeys);
            $abnormalKeys = array_slice($paramKeys, 0, 2);
        }
        // Теперь для каждого параметра генерируем значение:
        foreach ($telemetryParams as $key => $param) {
            list($ideal, $unit) = $param;
            if (in_array($key, $abnormalKeys)) {
                $telemetry[$key] = generateParam($ideal, $unit, $abnormalType);
            } else {
                $telemetry[$key] = generateParam($ideal, $unit, 'good');
            }
        }
    }

    // Старая логика генерации проблем оставлена для совместимости.
    $isProblematic = false;
    $issues = [
        'water' => false,
        'heating' => false,
        'electricity' => false,
        'sewage' => false
    ];
    if (mt_rand(1, 100) <= 30) {
        $issueTypes = array_keys($issues);
        $numIssues = mt_rand(1, 3);
        for ($j = 0; $j < $numIssues; $j++) {
            $type = $issueTypes[array_rand($issueTypes)];
            $issues[$type] = true;
            $isProblematic = true;
        }
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
        'telemetry' => $telemetry
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
    <!-- Если галочка стоит – дома с этой проблемой отображаются, если снята – скрываются -->
    <label><input type="checkbox" class="layer-toggle" value="water" checked> Вода</label><br>
    <label><input type="checkbox" class="layer-toggle" value="heating" checked> Отопление</label><br>
    <label><input type="checkbox" class="layer-toggle" value="electricity" checked> Электричество</label><br>
    <!-- Теперь для проблемы канализации учитывается значение влажности -->
    <label><input type="checkbox" class="layer-toggle" value="sewage" checked> Канализация</label>
</div>

<div id="map"></div>

<script>
    const houseData = <?php echo json_encode($houses); ?>;
</script>
<script src="js/map.js"></script>
</body>
</html>
