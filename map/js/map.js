ymaps.ready(init);

let map;
let allMarkers = [];
let displayMode = 'all'; // Возможные значения: "all" или "issues"

// Активные фильтры для проблем: по умолчанию все включены
let activeLayers = new Set(['water', 'heating', 'electricity', 'sewage']);

// Идеальные (эталонные) значения для каждого параметра
const idealValues = {
    water_temp: 50,      // °C
    water_pressure: 3,   // бар
    heat_temp: 60,       // °C
    heat_pressure: 2.5,  // бар
    voltage: 230,        // В
    humidity: 70         // %
};

// Функция вычисления статуса параметра.
// Возвращает: "normal", "deviation" или "emergency"
function getParamStatus(paramName, measuredStr) {
    const measuredVal = parseFloat(measuredStr);
    const ideal = idealValues[paramName];
    if (!ideal) return 'normal';
    const diffPercent = Math.abs(measuredVal - ideal) / ideal * 100;
    if (diffPercent <= 5) {
        return 'normal';
    } else if (diffPercent > 5 && diffPercent < 15) {
        return 'deviation';
    } else {
        return 'emergency';
    }
}

// Функция вычисления общего статуса дома.
// Если хотя бы один параметр аварийный – общий статус "emergency".
// Если есть отклонение (deviation), но нет аварии – статус "deviation".
// Иначе – "normal".
function computeOverallStatus(telemetry) {
    let hasDeviation = false;
    for (let key in telemetry) {
        const status = getParamStatus(key, telemetry[key]);
        if (status === 'emergency') return 'emergency';
        if (status === 'deviation') hasDeviation = true;
    }
    return hasDeviation ? 'deviation' : 'normal';
}

// Формирование содержимого балуна с выделением строк параметров.
function getBalloonContent(house) {
    let content = `<strong>Адрес:</strong> ${house.address}<br><table>`;
    for (let key in house.telemetry) {
        let status = getParamStatus(key, house.telemetry[key]);
        let displayName = '';
        switch (key) {
            case 'water_temp': displayName = 'Температура воды'; break;
            case 'water_pressure': displayName = 'Давление воды'; break;
            case 'heat_temp': displayName = 'Температура отопления'; break;
            case 'heat_pressure': displayName = 'Давление отопления'; break;
            case 'voltage': displayName = 'Напряжение'; break;
            case 'humidity': displayName = 'Влажность'; break;
            default: displayName = key;
        }
        // Выделяем строки с отклонениями: "deviation" – оранжевым, "emergency" – красным.
        let style = '';
        if (status === 'deviation') {
            style = 'style="color: darkorange; font-weight: bold;"';
        } else if (status === 'emergency') {
            style = 'style="color: red; font-weight: bold;"';
        }
        content += `<tr ${style}><td>${displayName}:</td><td>${house.telemetry[key]}</td></tr>`;
    }
    content += '</table>';
    return content;
}

// Выбор опций метки по общему статусу дома.
function getMarkerOptions(overallStatus) {
    let iconFile = '';
    switch(overallStatus) {
        case 'normal': iconFile = 'house-green.png'; break;
        case 'deviation': iconFile = 'house-yellow.png'; break;
        case 'emergency': iconFile = 'house-red.png'; break;
        default: iconFile = 'house-green.png';
    }
    return {
        iconLayout: 'default#image',
        iconImageHref: 'img/' + iconFile,
        iconImageSize: [32, 32],
        iconImageOffset: [-16, -16],
        visible: true
    };
}

function init() {
    map = new ymaps.Map("map", {
        center: [46.3497, 48.0408],
        zoom: 13
    });

    // Создаем метки для каждого дома, вычисляя для каждого общий статус по telemetry.
    houseData.forEach(house => {
        const overallStatus = computeOverallStatus(house.telemetry);
        // Сохраняем общий статус в объекте для дальнейшей фильтрации.
        house.overallStatus = overallStatus;
        
        const marker = new ymaps.Placemark(
            [house.lat, house.lng],
            {
                balloonContentHeader: `Дом №${house.id}`,
                balloonContentBody: getBalloonContent(house),
                hintContent: `Дом №${house.id}`
            },
            getMarkerOptions(overallStatus)
        );
        
        marker.properties.set('houseData', house);
        map.geoObjects.add(marker);
        allMarkers.push(marker);
    });

    // Обработчики переключателей режима отображения.
    document.querySelectorAll('input[name="mode"]').forEach(radio => {
        radio.addEventListener('change', () => {
            displayMode = radio.value;
            updateMarkers();
        });
    });

    // Обработчики для фильтров по проблемам.
    document.querySelectorAll('.layer-toggle').forEach(cb => {
        cb.addEventListener('change', () => {
            if (cb.checked) {
                activeLayers.add(cb.value);
            } else {
                activeLayers.delete(cb.value);
            }
            updateMarkers();
        });
    });

    updateMarkers();
}

// Функция фильтрации видимости меток.
// Если выбран режим "issues", показываются только дома с любой проблемой.
// К тому же, если дом имеет проблему по категории, для которой чекбокс снят, дом скрывается.
function updateMarkers() {
    allMarkers.forEach(marker => {
        const house = marker.properties.get('houseData');
        let showMarker = true;
        
        // Определяем проблемы по категориям.
        // Проблемы воды и отопления на основе соответствующих параметров.
        let waterProblem = (getParamStatus('water_temp', house.telemetry['water_temp']) !== 'normal' ||
                            getParamStatus('water_pressure', house.telemetry['water_pressure']) !== 'normal');
        let heatingProblem = (getParamStatus('heat_temp', house.telemetry['heat_temp']) !== 'normal' ||
                              getParamStatus('heat_pressure', house.telemetry['heat_pressure']) !== 'normal');
        let electricityProblem = (getParamStatus('voltage', house.telemetry['voltage']) !== 'normal');
        // Проблема канализации теперь определяется по параметру "humidity".
        let sewageProblem = (getParamStatus('humidity', house.telemetry['humidity']) !== 'normal');
        
        // Если выбран режим "issues", показываем только дома с хотя бы одной проблемой.
        let hasAnyProblem = waterProblem || heatingProblem || electricityProblem || sewageProblem;
        if (displayMode === 'issues' && !hasAnyProblem) {
            showMarker = false;
        }
        
        // Если дом имеет проблему по какой-либо категории, а фильтр для неё отключён, скрываем дом.
        if (waterProblem && !activeLayers.has('water')) showMarker = false;
        if (heatingProblem && !activeLayers.has('heating')) showMarker = false;
        if (electricityProblem && !activeLayers.has('electricity')) showMarker = false;
        if (sewageProblem && !activeLayers.has('sewage')) showMarker = false;
        
        marker.options.set('visible', showMarker);
    });
}
