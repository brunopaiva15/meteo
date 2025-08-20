<?php
error_reporting(0);

// Charger les données de prévision
try {
    $json_url = 'https://prevision-meteo.ch/services/json/villeret-be';
    $json_data = file_get_contents($json_url);
    $weather_data = json_decode($json_data, true);
} catch (Exception $e) {
    $erreur = 1;
}

// Charger les données actuelles depuis SwissMetNet (station COY)
$current_data = null;
try {
    // Données actuelles (température, humidité, vent)
    $smn_url = 'https://api.existenz.ch/apiv1/smn/latest?locations=COY&app=swissmetnet-display';
    $smn_json = file_get_contents($smn_url);
    $smn_response = json_decode($smn_json, true);

    if ($smn_response && isset($smn_response['payload'])) {
        $current_data = [];
        foreach ($smn_response['payload'] as $item) {
            if ($item['loc'] === 'COY') {
                switch ($item['par']) {
                    case 'tt':
                        $current_data['temperature'] = $item['val'];
                        break;
                    case 'rh':
                        $current_data['humidity'] = $item['val'];
                        break;
                    case 'ff':
                        $current_data['wind_speed'] = $item['val'];
                        break;
                    case 'dd':
                        $current_data['wind_direction'] = $item['val'];
                        break;
                    case 'fx':
                        $current_data['wind_gusts'] = $item['val'];
                        break;
                }
                $current_data['timestamp'] = $item['timestamp'];
            }
        }
    }

    // Récupérer les précipitations des dernières 24 heures
    $end_time = time();
    $start_time = $end_time - (24 * 3600); // 24 heures en arrière
    $precip_url = "https://api.existenz.ch/apiv1/smn/daterange?locations=COY&parameters=rr&start=" .
        date('Y-m-d\TH:i:s', $start_time) . "&end=" . date('Y-m-d\TH:i:s', $end_time) .
        "&app=swissmetnet-display";

    $precip_json = file_get_contents($precip_url);
    $precip_response = json_decode($precip_json, true);

    if ($precip_response && isset($precip_response['payload'])) {
        $total_precipitation_24h = 0;
        foreach ($precip_response['payload'] as $item) {
            if ($item['loc'] === 'COY' && $item['par'] === 'rr') {
                $total_precipitation_24h += $item['val'];
            }
        }
        $current_data['precipitation_24h'] = $total_precipitation_24h;
    }
} catch (Exception $e) {
    // Silently fail if SMN data not available
}

// Fonction pour convertir la direction du vent en texte
function getWindDirection($degrees)
{
    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    $index = round($degrees / 22.5) % 16;
    return $directions[$index];
}

function formatWind($data)
{
    if (!isset($data['wind_speed']) || !isset($data['wind_direction'])) return 'N/A';
    $dirTxt = getWindDirection($data['wind_direction']);
    $spd = number_format($data['wind_speed'], 1);
    $gust = isset($data['wind_gusts']) && $data['wind_gusts'] > 0
        ? " • rafales jusqu'à " . number_format($data['wind_gusts'], 1) . " km/h"
        : "";
    return "{$dirTxt} {$spd} km/h{$gust}";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Météo de la Semaine</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            margin: 0;
            padding: 0;
            color: #333;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #46B98C 0%, #3ea876 100%);
            color: white;
            padding: 15px 0;
            text-align: center;
            box-shadow: 0 4px 20px rgba(70, 185, 140, 0.3);
        }

        .header h1 {
            margin: 0;
            font-size: 2.3em;
            font-weight: 400;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 30px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .weather-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px 25px;
            margin: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: calc(20% - 20px);
            /* 5 cartes par ligne, moins l'espace du gap */
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: slideIn 0.6s forwards;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .weather-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
        }

        .current-live-card {
            background: linear-gradient(135deg, rgba(70, 185, 140, 0.1) 0%, rgba(255, 255, 255, 0.95) 100%);
            border: 2px solid #46B98C;
            box-shadow: 0 12px 40px rgba(70, 185, 140, 0.2);
        }

        .weather-icon-small {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.2em;
            color: #46B98C;
            opacity: 0.7;
        }

        h2 {
            margin: 0 0 20px 0;
            font-size: 1.9em;
            font-weight: 500;
            color: #2d3748;
        }

        .weather-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .weather-condition {
            font-size: 1.4em;
            color: #4a5568;
            min-height: 2.8em;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.3;
            font-weight: 500;
        }

        .temperature {
            font-size: 3.5em;
            font-weight: 300;
            color: #2d3748;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .weather-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .weather-info .tile {
            margin: 0 6px 6px 6px;
        }

        .weather-info .tile.no-wrap {
            white-space: nowrap;
            overflow: hidden;
        }

        .weather-info .no-break-group {
            display: flex;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .weather-info .no-break-group .tile {
            margin: 0 12px 6px 0;
        }

        .weather-info .tile i {
            margin-right: 8px;
            font-size: 1.1em;
            width: 20px;
            text-align: center;
        }

        .weather-info i {
            margin-right: 8px;
            font-size: 1.2em;
            width: 20px;
            text-align: center;
        }

        .temperature-range {
            font-size: 2.8em;
            font-weight: 300;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .temp-max {
            color: #e53e3e;
        }

        .temp-min {
            color: #3182ce;
        }

        .data-source {
            position: absolute;
            bottom: 8px;
            right: 12px;
            font-size: 0.7em;
            color: #718096;
            font-weight: 500;
        }

        /* Couleurs des icônes */
        .fa-thermometer-half {
            color: #e53e3e;
        }

        .fa-tint {
            color: #3182ce;
        }

        .fa-wind {
            color: #46B98C;
        }

        .fa-compass {
            color: #d69e2e;
        }

        .fa-satellite-dish {
            color: #46B98C;
        }

        .fa-gust {
            color: #38b2ac;
        }

        .fa-cloud-rain {
            color: #3182ce;
        }

        .fa-arrow-up {
            color: #e53e3e;
        }

        .fa-arrow-down {
            color: #3182ce;
        }

        .weather-info .period {
            font-size: 0.75em;
            opacity: 0.6;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        /* Animation différée pour chaque carte */
        .weather-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .weather-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .weather-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .weather-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .weather-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
                gap: 15px;
            }

            .weather-card {
                width: 100%;
                max-width: 350px;
            }

            .header h1 {
                font-size: 2.5em;
            }
        }

        @media (max-width: 480px) {
            .weather-info .tile {
                width: 100%;
                margin-bottom: 8px;
            }

            .temperature {
                font-size: 3em;
            }

            .temperature-range {
                font-size: 2.3em;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Météo à <?php echo $weather_data['city_info']['name']; ?></h1>
    </div>

    <div class="container">
        <!-- Section Actuellement avec données SwissMetNet -->
        <?php if ($current_data): ?>
            <div class="weather-card current-live-card">
                <i class="fas fa-satellite-dish weather-icon-small"></i>
                <h2>Actuellement</h2>
                <?php if (isset($weather_data['current_condition'])): ?>
                    <img class="weather-icon" src="<?php echo $weather_data['current_condition']['icon_big']; ?>" alt="Icon">
                    <div class="weather-condition"><?php echo $weather_data['current_condition']['condition']; ?></div>
                <?php endif; ?>
                <div class="temperature"><?php echo round($current_data['temperature'], 1); ?>°C</div>
                <div class="weather-info">
                    <div class="tile">
                        <i class="fas fa-wind"></i>
                        <?php echo formatWind($current_data); ?>
                    </div>
                    <div class="no-break-group">
                        <div class="tile">
                            <i class="fas fa-tint"></i>
                            <?php echo isset($current_data['humidity']) ? round($current_data['humidity']) . '%' : 'N/A'; ?>
                        </div>
                        <div class="tile">
                            <i class="fas fa-cloud-rain"></i>
                            <span id="precipitation-display">
                                <?php
                                $precip_10min = isset($current_data['precipitation']) ? $current_data['precipitation'] : 0;
                                $precip_24h = isset($current_data['precipitation_24h']) ? $current_data['precipitation_24h'] : 0;
                                echo number_format($precip_24h, 1) . ' mm <span class="period">(24h)</span>';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="data-source">MétéoSuisse</div>
            </div>
        <?php endif; ?>

        <!-- Prévisions existantes -->
        <?php
        // Get tomorrow week day in french
        $tomorrowdate = date('l', strtotime('+1 day'));
        $tomorrowdate = str_replace('Monday', 'Lundi', $tomorrowdate);
        $tomorrowdate = str_replace('Tuesday', 'Mardi', $tomorrowdate);
        $tomorrowdate = str_replace('Wednesday', 'Mercredi', $tomorrowdate);
        $tomorrowdate = str_replace('Thursday', 'Jeudi', $tomorrowdate);
        $tomorrowdate = str_replace('Friday', 'Vendredi', $tomorrowdate);
        $tomorrowdate = str_replace('Saturday', 'Samedi', $tomorrowdate);
        $tomorrowdate = str_replace('Sunday', 'Dimanche', $tomorrowdate);
        $twodaysdate = date('l', strtotime('+2 day'));
        $twodaysdate = str_replace('Monday', 'Lundi', $twodaysdate);
        $twodaysdate = str_replace('Tuesday', 'Mardi', $twodaysdate);
        $twodaysdate = str_replace('Wednesday', 'Mercredi', $twodaysdate);
        $twodaysdate = str_replace('Thursday', 'Jeudi', $twodaysdate);
        $twodaysdate = str_replace('Friday', 'Vendredi', $twodaysdate);
        $twodaysdate = str_replace('Saturday', 'Samedi', $twodaysdate);
        $twodaysdate = str_replace('Sunday', 'Dimanche', $twodaysdate);
        $threedays = date('l', strtotime('+3 day'));
        $threedays = str_replace('Monday', 'Lundi', $threedays);
        $threedays = str_replace('Tuesday', 'Mardi', $threedays);
        $threedays = str_replace('Wednesday', 'Mercredi', $threedays);
        $threedays = str_replace('Thursday', 'Jeudi', $threedays);
        $threedays = str_replace('Friday', 'Vendredi', $threedays);
        $threedays = str_replace('Saturday', 'Samedi', $threedays);
        $threedays = str_replace('Sunday', 'Dimanche', $threedays);
        $days = ['fcst_day_0' => 'Aujourd\'hui', 'fcst_day_1' => $tomorrowdate, 'fcst_day_2' => $twodaysdate, 'fcst_day_3' => $threedays];
        foreach ($days as $day => $title) {
        ?>
            <div class="weather-card">
                <h2><?php echo $title; ?></h2>
                <img class="weather-icon" src="<?php echo $weather_data[$day]['icon_big']; ?>" alt="Icon">
                <div class="weather-condition"><?php echo $weather_data[$day]['condition']; ?></div>

                <?php if ($day === 'current_condition') { ?>
                    <div class="temperature"><?php echo $weather_data[$day]['tmp']; ?>°C</div>
                    <div class="weather-info">
                        <div><i class="fas fa-wind"></i><?php echo $weather_data[$day]['wnd_spd']; ?> km/h</div>
                        <div><i class="fas fa-tint"></i><?php echo $weather_data[$day]['humidity']; ?>%</div>
                    </div>
                <?php } else { ?>
                    <div class="temperature-range">
                        <span class="temp-max"><?php echo $weather_data[$day]['tmax']; ?>°</span>
                        <span style="color: #a0aec0;"> / </span>
                        <span class="temp-min"><?php echo $weather_data[$day]['tmin']; ?>°</span>
                    </div>
                <?php } ?>
            </div>
        <?php
        }
        ?>
    </div>

    <script>
        // Alternance automatique entre précipitations 24h et 10min
        let showingDaily = true;
        const precip10min = <?php echo isset($current_data['precipitation']) ? $current_data['precipitation'] : 0; ?>;
        const precip24h = <?php echo isset($current_data['precipitation_24h']) ? $current_data['precipitation_24h'] : 0; ?>;

        function togglePrecipitation() {
            const display = document.getElementById('precipitation-display');
            if (display) {
                if (showingDaily) {
                    display.innerHTML = precip10min.toFixed(1) + ' mm <span class="period">(10m)</span>';
                } else {
                    display.innerHTML = precip24h.toFixed(1) + ' mm <span class="period">(24h)</span>';
                }
                showingDaily = !showingDaily;
            }
        }

        // Alterner toutes les 5 secondes
        setInterval(togglePrecipitation, 5000);
    </script>
</body>

</html>
