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
    <title>Цифровая карта ЖКХ — Презентация и демонстрация</title>
    <!-- Стили Reveal.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@4.4.0/dist/reveal.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@4.4.0/dist/theme/white.css" id="theme">
    <style>
        /* Стили для презентации */
        .reveal section {
            text-align: center;
        }
        /* Стили для карты, чтобы растянуть на весь слайд */
        #map {
            width: 100%;
            height: 100vh;
        }

        /* Если есть другие элементы: например, корректировка отступов */
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

        /* Дополнительные стили для слайдов */
        img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
    <!-- Подключение Яндекс.Карт API -->
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>
</head>
<body>

<!-- Структура Reveal.js -->
<div class="reveal">
    <div class="slides">
        <!-- Слайды презентации -->
        <section>
            <h2>Цифровая карта ЖКХ</h2>
            <p>Мониторинг коммунальных услуг в реальном времени</p>
            <p><strong>Пилотный проект — Астрахань, 2025</strong></p>
            <img src="img/slide1-title.webp" alt="Обложка проекта">
        </section>

        <section>
            <h3>Проблематика</h3>
            <ul>
                <li>Нет централизованного мониторинга ЖКХ</li>
                <li>Реакция служб — только по жалобам</li>
                <li>Аварии устраняются поздно</li>
                <li>Нет визуализации данных по городу</li>
            </ul>
            <img src="img/slide2-problem.webp" alt="Проблемы ЖКХ">
        </section>

        <section>
            <h3>Цели проекта</h3>
            <ul>
                <li>Онлайн-мониторинг домов</li>
                <li>Прозрачность работы УК и служб</li>
                <li>Снижение аварий и жалоб</li>
                <li>Цифровизация ЖКХ</li>
            </ul>
            <img src="img/slide3-goal.webp" alt="Цели проекта">
        </section>

        <section>
            <h3>Функциональные возможности</h3>
            <ul>
                <li>Интерактивная карта города</li>
                <li>Слои по видам ресурсов: вода, отопление, электричество</li>
                <li>Параметры: температура, давление, влажность, напряжение</li>
                <li>Подсветка аномалий: 🔴 отклонения, 🟡 предупреждения</li>
            </ul>
            <img src="img/slide4-functions.webp" alt="Функциональность системы">
        </section>

        <section>
            <h3>Контролируемые параметры</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="margin: 0 auto;">
                <thead>
                    <tr>
                        <th>Ресурс</th>
                        <th>Параметры</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>ХВС / ГВС</td><td>Температура, давление</td></tr>
                    <tr><td>Отопление</td><td>Температура подачи и обратки, давление</td></tr>
                    <tr><td>Электроснабжение</td><td>Напряжение, нагрузка</td></tr>
                    <tr><td>Подвальные помещения</td><td>Влажность, затопления</td></tr>
                </tbody>
            </table>
            <img src="img/slide5-params.webp" alt="Параметры мониторинга">
        </section>

        <section>
            <h3>Архитектура решения</h3>
            <p>Датчики → Контроллер → Ethernet-сеть → Сервер → Веб-интерфейс</p>
            <img src="img/slide6-architecture.webp" alt="Архитектура системы">
        </section>

        <section>
            <h3>Интерфейс карты</h3>
            <p>Карта города Астрахань с цветовой индикацией состояния домов</p>
            <ul>
                <li>🟢 Норма</li>
                <li>🟡 Предупреждение</li>
                <li>🔴 Авария</li>
            </ul>
            <img src="img/slide7-map.webp" alt="Интерактивная карта ЖКХ">
        </section>

        <section>
            <h3>Сценарий работы</h3>
            <ol>
                <li>Установка оборудования в доме</li>
                <li>Сбор данных → сервер</li>
                <li>Отображение на карте</li>
                <li>Оповещения при отклонениях</li>
            </ol>
            <img src="img/slide8-scenario.webp" alt="Сценарий работы системы">
        </section>

        <section>
            <h3>Этапы внедрения</h3>
            <ul>
                <li>Пилот (50 домов)</li>
                <li>Анализ, корректировка</li>
                <li>Масштабирование</li>
            </ul>
            <img src="img/slide9-steps.webp" alt="Этапы реализации проекта">
        </section>

        <section>
            <h3>Смета пилотного проекта</h3>
            <ul>
                <li>Оборудование: 1.7 млн ₽</li>
                <li>Разработка ПО: 2.1 млн ₽</li>
                <li>Сервер + поддержка: 0.36 млн ₽</li>
                <li>Монтаж: 0.5 млн ₽</li>
                <li><strong>Итого: 4.72 млн ₽</strong></li>
            </ul>
            <img src="img/slide10-budget.webp" alt="Бюджет проекта">
        </section>

        <section>
            <h3>Ожидаемый эффект</h3>
            <ul>
                <li>Контроль ЖКХ в реальном времени</li>
                <li>Прозрачность перед горожанами</li>
                <li>Снижение аварий</li>
                <li>Экономия ресурсов</li>
            </ul>
            <img src="img/slide11-effect.webp" alt="Ожидаемые результаты">
        </section>

        <section>
            <h3>Заключение</h3>
            <p>Готовность к пилотному запуску</p>
            <p>Масштабируемость на весь город</p>
            <p>Поддержка цифровизации ЖКХ в Астрахани</p>
            <img src="img/slide12-end.webp" alt="Финальный слайд — цифровой город">
        </section>

        <!-- Последний слайд: демонстрация интерактивной карты -->
        <section>
            <!-- При необходимости можно добавить заголовок -->
            <h3>Демонстрация карты ЖКХ</h3>
            <!-- Контейнер для карты -->
            <div id="map"></div>

            <!-- Контролы слоёв (при необходимости можно разместить внутри слайда) -->
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
        </section>
    </div>
</div>

<!-- Передаём данные карт домов в js -->
<script>
    const houseData = <?php echo json_encode($houses); ?>;
</script>

<!-- Подключаем скрипты -->
<script src="https://cdn.jsdelivr.net/npm/reveal.js@4.4.0/dist/reveal.min.js"></script>
<script>
// Инициализация Reveal.js
Reveal.initialize();
</script>

<!-- Скрипт для инициализации карты -->
<script src="js/map.js"></script>
</body>
</html>
