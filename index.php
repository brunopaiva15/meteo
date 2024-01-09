<?php
$json_url = 'https://prevision-meteo.ch/services/json/villeret-be';
$json_data = file_get_contents($json_url);
$weather_data = json_decode($json_data, true);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Météo de la Semaine</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        h1 {
            background-color: #46B98C;
            color: white;
            text-align: center;
            margin: 0;
            padding: 20px;
        }

        h2 {
            margin: 0;
            padding: 2;
            font-size: 1.9em;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
        }

        .weather-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
            margin: 10px;
            opacity: 0;
            transform: translateY(-20px);
            animation: slideIn 0.5s forwards;
        }

        .weather-icon {
            max-width: 100px;
            margin: 15px;
        }

        .weather-info {
            display: flex;
            /* grid changé en flex */
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .weather-info div {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50%;
            font-size: 1.7em;
            /* Ajouté pour répartir les éléments sur deux colonnes */
        }

        .weather-info i {
            margin-right: 10px;
            font-size: 1.5em;
            padding: 5px;
        }

        .weather-condition {
            font-size: 1.7em;
        }

        .current-day {
            border: 2px solid #4CAF50;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fa-thermometer-half {
            color: #FF5722;
        }

        .fa-cloud {
            color: #607D8B;
        }

        .fa-wind {
            color: #9E9E9E;
        }

        .fa-tint {
            color: #03A9F4;
        }
    </style>
</head>

<body>
    <h1>Météo à <?php echo $weather_data['city_info']['name']; ?></h1>
    <div class="container">
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
        $days = ['current_condition' => 'Actuellement', 'fcst_day_0' => 'Aujourd\'hui', 'fcst_day_1' => $tomorrowdate, 'fcst_day_2' => $twodaysdate, 'fcst_day_3' => $threedays];
        foreach ($days as $day => $title) {
        ?>
            <div class="weather-card <?php echo $day === 'current_condition' ? 'current-day' : ''; ?>">
                <h2><?php echo $title; ?></h2>
                <img class="weather-icon" src="<?php echo $weather_data[$day]['icon_big']; ?>" alt="Icon">
                <div class="weather-condition"><?php echo $weather_data[$day]['condition']; ?></div><br>
                <div class="weather-info">
                    <?php if ($day === 'current_condition') { ?>
                        <div><i class="fas fa-thermometer-half"></i><?php echo $weather_data[$day]['tmp']; ?>°C</div>
                    <?php } else { ?>
                        <div>
                            <i class="fas fa-thermometer-half"></i>
                            <i class="fas fa-arrow-up" style="font-size: 0.8em;"></i><?php echo $weather_data[$day]['tmax']; ?>°C&nbsp;&nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 0.8em;"></i><?php echo $weather_data[$day]['tmin']; ?>°C
                        </div>
                    <?php } ?>
                    <?php if ($day === 'current_condition') { ?>
                        <div><i class="fas fa-wind"></i><?php echo $weather_data[$day]['wnd_spd']; ?> Km/h</div>
                        <div><i class="fas fa-tint"></i><?php echo $weather_data[$day]['humidity']; ?>%</div>
                    <?php } ?>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</body>

</html>
