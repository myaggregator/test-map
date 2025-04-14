ymaps.ready(init);

let map;
let allMarkers = [];
let activeLayers = new Set(['water', 'heating', 'electricity', 'sewage']);
let displayMode = 'all'; // режим по умолчанию

function init() {
    map = new ymaps.Map("map", {
        center: [46.3497, 48.0408],
        zoom: 13
    });

    houseData.forEach(house => {
        const marker = new ymaps.Placemark(
            [house.lat, house.lng],
            {
                balloonContentHeader: `Дом №${house.id}`,
                balloonContentBody: `
                    <strong>Адрес:</strong> ${house.address}<br>
                    <strong>Температура воды:</strong> ${house.telemetry.water_temp}<br>
                    <strong>Давление воды:</strong> ${house.telemetry.water_pressure}<br>
                    <strong>Температура отопления:</strong> ${house.telemetry.heat_temp}<br>
                    <strong>Давление отопления:</strong> ${house.telemetry.heat_pressure}<br>
                    <strong>Напряжение:</strong> ${house.telemetry.voltage}<br>
                    <strong>Влажность:</strong> ${house.telemetry.humidity}
                `,
                hintContent: `Дом №${house.id}`
            },
            getMarkerOptions(house.issues)
        );

        marker.properties.set('houseData', house);
        map.geoObjects.add(marker);
        allMarkers.push(marker);
    });

    document.querySelectorAll('input[name="mode"]').forEach(radio => {
        radio.addEventListener('change', () => {
            displayMode = radio.value;
            updateMarkers();
        });
    });

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

function updateMarkers() {
    allMarkers.forEach(marker => {
        const house = marker.properties.get('houseData');
        const issues = house.issues;
        const hasIssue = Object.values(issues).some(v => v);

        let showMarker = false;

        if (displayMode === 'issues') {
            showMarker = Object.entries(issues).some(([key, value]) => value && activeLayers.has(key));
        } else if (displayMode === 'all') {
            if (!hasIssue) {
                showMarker = true;
            } else {
                showMarker = Object.entries(issues).some(([key, value]) => value && activeLayers.has(key));
            }
        }

        marker.options.set('visible', showMarker);
    });
}

function getMarkerOptions(issues) {
    const hasIssue = Object.values(issues).some(Boolean);
    const iconFile = hasIssue ? 'house-red.png' : 'house-green.png';

    return {
        iconLayout: 'default#image',
        iconImageHref: 'img/' + iconFile,
        iconImageSize: [32, 32],
        iconImageOffset: [-16, -16],
        visible: true
    };
}