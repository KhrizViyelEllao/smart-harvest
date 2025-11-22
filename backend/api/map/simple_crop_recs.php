<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db_connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$fieldId = isset($_GET['field_id']) ? (int)$_GET['field_id'] : 0;
if ($fieldId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid field_id.',
        'recommendations' => []
    ]);
    exit;
}

function computeCentroidLatLng(array $geometry): ?array
{
    if (!isset($geometry['type'], $geometry['coordinates'])) {
        return null;
    }

    $coords = [];
    switch ($geometry['type']) {
        case 'Polygon':
            $coords = $geometry['coordinates'][0] ?? [];
            break;
        case 'MultiPolygon':
            $coords = $geometry['coordinates'][0][0] ?? [];
            break;
    }

    if (empty($coords)) {
        return null;
    }

    $sumLat = 0.0;
    $sumLng = 0.0;
    $count  = 0;

    foreach ($coords as $pair) {
        if (!is_array($pair) || count($pair) < 2) {
            continue;
        }
        $sumLng += (float)$pair[0];
        $sumLat += (float)$pair[1];
        $count++;
    }

    if ($count === 0) {
        return null;
    }

    return [
        'lat' => $sumLat / $count,
        'lng' => $sumLng / $count
    ];
}

function fetchCurrentWeather(float $lat, float $lng): ?array
{
    $apiKey = getenv('OPENWEATHER_KEY') ?: '4cac84b627ac52ac5a76e3b3e2349132';
    if (!$apiKey) {
        return null;
    }

    $url = sprintf(
        'https://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&appid=%s&units=metric',
        $lat,
        $lng,
        $apiKey
    );

    $context = stream_context_create([
        'http' => ['timeout' => 6]
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return null;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return null;
    }

    return [
        'location'    => $json['name'] ?? 'Unknown',
        'temperature' => $json['main']['temp'] ?? null,
        'humidity'    => $json['main']['humidity'] ?? null,
        'rain'        => isset($json['rain']['1h']) ? 'yes' : 'no',
        'description' => $json['weather'][0]['description'] ?? 'N/A',
        'timestamp'   => date('Y-m-d H:i:s')
    ];
}

try {
    $stmt = $conn->prepare("
        SELECT field_id, name, type, area, notes, geometry
        FROM fields
        WHERE field_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $fieldId);
    $stmt->execute();
    $field = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$field) {
        echo json_encode([
            'success' => false,
            'message' => 'Field not found.',
            'recommendations' => []
        ]);
        exit;
    }

    $geometryJson = $field['geometry'] ? json_decode($field['geometry'], true) : null;
    $centroid     = $geometryJson ? computeCentroidLatLng($geometryJson) : null;

    $weather = null;
    if ($centroid) {
        $weather = fetchCurrentWeather((float)$centroid['lat'], (float)$centroid['lng']);
    }

    $season = in_array((int)date('n'), [5,6,7,8,9,10], true) ? 'wet' : 'dry';
    $area   = (float)($field['area'] ?? 0);
    $type   = strtolower((string)($field['type'] ?? ''));

    $cropProfiles = [
        // ==================== GRAINS & CEREALS ====================
        [
            'crop_name'     => 'Rice',
            'optimal_temp'  => '26-28°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 120,
            'best_suited'   => ['non-organic', 'transitioning'],
            'notes'         => 'Ideal for irrigated lowland fields; thrives in wet season with consistent water supply.'
        ],
        [
            'crop_name'     => 'Corn',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 100,
            'best_suited'   => ['non-organic', 'organic'],
            'notes'         => 'Reliable rotation crop; tolerates varying soils and performs well in dry season.'
        ],


        // ==================== LEAFY VEGETABLES ====================
        [
            'crop_name'     => 'Pechay',
            'optimal_temp'  => '19-21°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 40,
            'best_suited'   => ['organic'],
            'notes'         => 'Fast-growing leafy vegetable; high market demand in urban areas.'
        ],
        [
            'crop_name'     => 'Kangkong',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Very High',
            'season'        => 'wet',
            'duration_days' => 30,
            'best_suited'   => ['organic', 'transitioning'],
            'notes'         => 'Thrives in waterlogged conditions; can be harvested multiple times.'
        ],
        [
            'crop_name'     => 'Lettuce',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 50,
            'best_suited'   => ['organic'],
            'notes'         => 'Prefers cooler temperatures; high-value crop for restaurants and markets.'
        ],
        [
            'crop_name'     => 'Spinach',
            'optimal_temp'  => '20-22°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 45,
            'best_suited'   => ['organic'],
            'notes'         => 'Nutrient-rich leafy green; grows quickly in fertile soil.'
        ],

        // ==================== FRUITING VEGETABLES ====================
        [
            'crop_name'     => 'Tomato',
            'optimal_temp'  => '22-24°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 90,
            'best_suited'   => ['organic'],
            'notes'         => 'High-value crop; requires staking and careful pest management.'
        ],
        [
            'crop_name'     => 'Eggplant',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 110,
            'best_suited'   => ['organic', 'transitioning'],
            'notes'         => 'Produces continuously for several months; popular in Filipino dishes.'
        ],
        [
            'crop_name'     => 'Bell Pepper',
            'optimal_temp'  => '23-25°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 85,
            'best_suited'   => ['organic'],
            'notes'         => 'High-value crop; requires well-drained soil and consistent moisture.'
        ],
        [
            'crop_name'     => 'Chili Pepper',
            'optimal_temp'  => '25-27°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 95,
            'best_suited'   => ['organic'],
            'notes'         => 'Spicy varieties have good market demand; drought-tolerant once established.'
        ],
        [
            'crop_name'     => 'Cucumber',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 60,
            'best_suited'   => ['organic'],
            'notes'         => 'Fast-growing vine crop; requires trellising for straight fruits.'
        ],
        [
            'crop_name'     => 'Squash',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 70,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Versatile vegetable; both fruits and flowers are edible.'
        ],
        [
            'crop_name'     => 'Okra',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 55,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Heat-loving crop; produces continuously throughout the season.'
        ],

        // ==================== ROOT CROPS ====================
        [
            'crop_name'     => 'Sweet Potato',
            'optimal_temp'  => '23-25°C',
            'water_needs'   => 'Low',
            'season'        => 'wet',
            'duration_days' => 120,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Drought-tolerant; both tubers and leaves are edible and nutritious.'
        ],
        [
            'crop_name'     => 'Cassava',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Low',
            'season'        => 'wet',
            'duration_days' => 240,
            'best_suited'   => ['non-organic', 'transitioning'],
            'notes'         => 'Very drought-resistant; can grow in poor soils with minimal care.'
        ],
        [
            'crop_name'     => 'Carrot',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 80,
            'best_suited'   => ['organic'],
            'notes'         => 'Requires deep, loose soil for straight root development.'
        ],
        [
            'crop_name'     => 'Radish',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 30,
            'best_suited'   => ['organic'],
            'notes'         => 'Very fast-growing; good for quick cash flow between main crops.'
        ],
        [
            'crop_name'     => 'Potato',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 100,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Prefers cooler highland conditions; requires well-drained soil.'
        ],

        // ==================== LEGUMES ====================
        [
            'crop_name'     => 'Peanut',
            'optimal_temp'  => '25-27°C',
            'water_needs'   => 'Low',
            'season'        => 'dry',
            'duration_days' => 95,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Improves soil nitrogen; good rotational crop after rice or corn.'
        ],
        [
            'crop_name'     => 'Mung Bean',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Low',
            'season'        => 'dry',
            'duration_days' => 65,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Drought-tolerant; fixes nitrogen in soil; beans and sprouts are marketable.'
        ],
        [
            'crop_name'     => 'Soybean',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 90,
            'best_suited'   => ['non-organic', 'transitioning'],
            'notes'         => 'High-protein legume; used for tofu, soy milk, and animal feed.'
        ],
        [
            'crop_name'     => 'String Beans',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 50,
            'best_suited'   => ['organic'],
            'notes'         => 'Continuous harvest crop; requires trellising for better yield.'
        ],

        // ==================== FRUITS ====================
        [
            'crop_name'     => 'Banana',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 365,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Year-round production; requires consistent moisture and wind protection.'
        ],
        [
            'crop_name'     => 'Pineapple',
            'optimal_temp'  => '26-28°C',
            'water_needs'   => 'Low',
            'season'        => 'wet',
            'duration_days' => 540,
            'best_suited'   => ['non-organic', 'transitioning'],
            'notes'         => 'Drought-tolerant once established; grows well in acidic soils.'
        ],
        [
            'crop_name'     => 'Papaya',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 300,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Fast-growing fruit tree; produces year-round in tropical climate.'
        ],
        [
            'crop_name'     => 'Mango',
            'optimal_temp'  => '26-28°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 1095,
            'best_suited'   => ['non-organic', 'organic'],
            'notes'         => 'National fruit; requires dry season for flowering and fruit setting.'
        ],
        [
            'crop_name'     => 'Calamansi',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 730,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Philippine lime; produces year-round; high demand for juice and flavoring.'
        ],
        [
            'crop_name'     => 'Watermelon',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 85,
            'best_suited'   => ['non-organic', 'organic'],
            'notes'         => 'Requires plenty of space and water; popular summer fruit.'
        ],
        [
            'crop_name'     => 'Jackfruit',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 1825,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Largest tree-borne fruit; drought-tolerant once established.'
        ],
        [
            'crop_name'     => 'Coconut',
            'optimal_temp'  => '28-30°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 2555,
            'best_suited'   => ['non-organic', 'organic'],
            'notes'         => 'Tree of life; produces multiple products; salt-tolerant.'
        ],
        [
            'crop_name'     => 'Avocado',
            'optimal_temp'  => '21-23°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 1460,
            'best_suited'   => ['organic'],
            'notes'         => 'Prefers cooler highland areas; growing demand in health-conscious markets.'
        ],
        [
            'crop_name'     => 'Guava',
            'optimal_temp'  => '25-27°C',
            'water_needs'   => 'Low',
            'season'        => 'wet',
            'duration_days' => 730,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Drought-resistant; produces vitamin C-rich fruits year-round.'
        ],

        // ==================== COMMERCIAL CROPS ====================
        [
            'crop_name'     => 'Sugarcane',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 365,
            'best_suited'   => ['non-organic'],
            'notes'         => 'Major commercial crop; requires abundant water and fertile soil.'
        ],
        [
            'crop_name'     => 'Coffee',
            'optimal_temp'  => '19-21°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 1095,
            'best_suited'   => ['organic'],
            'notes'         => 'Prefers highland areas with cool temperatures; high-value export crop.'
        ],
        [
            'crop_name'     => 'Cacao',
            'optimal_temp'  => '25-27°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 1825,
            'best_suited'   => ['organic'],
            'notes'         => 'Requires shade when young; premium beans for chocolate production.'
        ],

                [
            'crop_name'     => 'Atis (Sugar Apple)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Low',
            'season'        => 'wet',
            'duration_days' => 730,
            'best_suited'   => ['organic'],
            'notes'         => 'Sweet tropical fruit; drought-tolerant once established; popular dessert fruit.'
        ],
        [
            'crop_name'     => 'Guyabano (Soursop)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 1095,
            'best_suited'   => ['organic'],
            'notes'         => 'Medicinal properties; large green fruit with white pulp; prefers humid climate.'
        ],
        [
            'crop_name'     => 'Lanzones',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 1825,
            'best_suited'   => ['organic'],
            'notes'         => 'Seasonal fruit; clusters of sweet-tangy fruits; requires consistent moisture.'
        ],
        [
            'crop_name'     => 'Rambutan',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 1460,
            'best_suited'   => ['organic'],
            'notes'         => 'Hairy red fruit; sweet and juicy; popular in local markets and exports.'
        ],
        [
            'crop_name'     => 'Santol',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 1825,
            'best_suited'   => ['organic', 'non-organic'],
            'notes'         => 'Hardy tree; yellow-orange fruit with sweet-sour pulp; used for fresh eating and preserves.'
        ],
        [
            'crop_name'     => 'Chico (Sapodilla)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Low',
            'season'        => 'dry',
            'duration_days' => 1825,
            'best_suited'   => ['organic'],
            'notes'         => 'Brown fruit with sweet, grainy pulp; drought-resistant; good for dry areas.'
        ],
        [
            'crop_name'     => 'Duhat (Java Plum)',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Low',
            'season'        => 'wet',
            'duration_days' => 1460,
            'best_suited'   => ['organic'],
            'notes'         => 'Purple-black berries; astringent taste when fresh, sweet when ripe; medicinal uses.'
        ],
        [
            'crop_name'     => 'Kamias (Bilimbi)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 365,
            'best_suited'   => ['organic'],
            'notes'         => 'Very sour green fruit; used as souring agent in sinigang and other dishes.'
        ],
        [
            'crop_name'     => 'Balimbing (Star Fruit)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 730,
            'best_suited'   => ['organic'],
            'notes'         => 'Star-shaped fruit; sweet and juicy; rich in vitamin C; grows quickly.'
        ],
        [
            'crop_name'     => 'Sampaloc (Tamarind)',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Low',
            'season'        => 'dry',
            'duration_days' => 2555,
            'best_suited'   => ['organic'],
            'notes'         => 'Sour pod fruit; used for sinigang, candies, and sauces; very drought-tolerant.'
        ],
        [
            'crop_name'     => 'Kalamansi (Calamansi)',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 730,
            'best_suited'   => ['organic'],
            'notes'         => 'Philippine lime; essential for Filipino cuisine; produces year-round.'
        ],
        [
            'crop_name'     => 'Ube (Purple Yam)',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 240,
            'best_suited'   => ['organic'],
            'notes'         => 'Purple tuber; popular for desserts like halo-halo and ube jam; high market value.'
        ],
        [
            'crop_name'     => 'Gabi (Taro)',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'High',
            'season'        => 'wet',
            'duration_days' => 210,
            'best_suited'   => ['organic'],
            'notes'         => 'Starchy root crop; leaves and corms are edible; used in laing and ginataan.'
        ],
        [
            'crop_name'     => 'Malunggay (Moringa)',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Low',
            'season'        => 'dry',
            'duration_days' => 180,
            'best_suited'   => ['organic'],
            'notes'         => 'Highly nutritious leaves; drought-resistant; used in soups and as health supplement.'
        ],
        [
            'crop_name'     => 'Sili (Chili)',
            'optimal_temp'  => '25-27°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 95,
            'best_suited'   => ['organic'],
            'notes'         => 'Various types from mild to very spicy; essential for Filipino cooking and condiments.'
        ],
        [
            'crop_name'     => 'Sitaw (String Beans)',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 50,
            'best_suited'   => ['organic'],
            'notes'         => 'Long green pods; continuous harvest; popular in adobong sitaw and other dishes.'
        ],
        [
            'crop_name'     => 'Talong (Eggplant)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 110,
            'best_suited'   => ['organic'],
            'notes'         => 'Purple elongated fruit; staple in torta, sinigang, and grilled dishes.'
        ],
        [
            'crop_name'     => 'Ampalaya (Bitter Melon)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 75,
            'best_suited'   => ['organic'],
            'notes'         => 'Bitter green vegetable; medicinal properties; used in pinakbet and stir-fries.'
        ],
        [
            'crop_name'     => 'Pechay (Bok Choy)',
            'optimal_temp'  => '19-21°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 40,
            'best_suited'   => ['organic'],
            'notes'         => 'Fast-growing leafy vegetable; essential for chopsuey and noodle dishes.'
        ],
        [
            'crop_name'     => 'Kangkong (Water Spinach)',
            'optimal_temp'  => '29-31°C',
            'water_needs'   => 'Very High',
            'season'        => 'wet',
            'duration_days' => 30,
            'best_suited'   => ['organic'],
            'notes'         => 'Semi-aquatic leafy green; grows in waterlogged areas; used in adobong kangkong.'
        ],
        [
            'crop_name'     => 'Mustasa (Mustard)',
            'optimal_temp'  => '20-22°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 45,
            'best_suited'   => ['organic'],
            'notes'         => 'Picked leaves used for burong mustasa; grows well in cool highland areas.'
        ],
        [
            'crop_name'     => 'Labanos (Radish)',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 30,
            'best_suited'   => ['organic'],
            'notes'         => 'White root vegetable; fast-growing; used in sinigang and fresh salads.'
        ],
        [
            'crop_name'     => 'Repolyo (Cabbage)',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 80,
            'best_suited'   => ['organic'],
            'notes'         => 'Cool-season crop; forms tight heads; used in lumpia and stir-fries.'
        ],
        [
            'crop_name'     => 'Letsugas (Lettuce)',
            'optimal_temp'  => '17-19°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 50,
            'best_suited'   => ['organic'],
            'notes'         => 'Leafy green for salads and burgers; prefers cooler temperatures.'
        ],
        [
            'crop_name'     => 'Pipino (Cucumber)',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'High',
            'season'        => 'dry',
            'duration_days' => 60,
            'best_suited'   => ['organic'],
            'notes'         => 'Refreshing fruit; used fresh in salads and as pickles; requires trellising.'
        ],
        [
            'crop_name'     => 'Kalabasa (Squash)',
            'optimal_temp'  => '24-26°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 70,
            'best_suited'   => ['organic'],
            'notes'         => 'Versatile vegetable; flowers, fruits, and shoots are all edible in Filipino cuisine.'
        ],
        [
            'crop_name'     => 'Patola (Sponge Gourd)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 65,
            'best_suited'   => ['organic'],
            'notes'         => 'Long ridged gourd; used in soups like tinola and misua; requires trellising.'
        ],
        [
            'crop_name'     => 'Upo (Bottle Gourd)',
            'optimal_temp'  => '27-29°C',
            'water_needs'   => 'Medium',
            'season'        => 'wet',
            'duration_days' => 70,
            'best_suited'   => ['organic'],
            'notes'         => 'Large light-green gourd; mild flavor; used in ginisa and soup dishes.'
        ],
        [
            'crop_name'     => 'Sayote (Chayote)',
            'optimal_temp'  => '19-21°C',
            'water_needs'   => 'Medium',
            'season'        => 'dry',
            'duration_days' => 120,
            'best_suited'   => ['organic'],
            'notes'         => 'Pear-shaped vegetable; vine crop; fruits, shoots, and roots are all edible.'
        ]
    ];

    $recommendations = [];
    foreach ($cropProfiles as $profile) {
        $score = 0.55;

        if ($profile['season'] === $season) {
            $score += 0.20;
        }

        if ($type && in_array($type, $profile['best_suited'], true)) {
            $score += 0.12;
        }

        if ($area >= 5000 && in_array($profile['crop_name'], ['Rice', 'Corn', 'Sugarcane'], true)) {
            $score += 0.08;
        } elseif ($area < 3000 && in_array($profile['crop_name'], ['Tomato', 'Bell Pepper', 'Pechay'], true)) {
            $score += 0.08;
        } elseif ($area >= 10000 && in_array($profile['crop_name'], ['Banana', 'Coconut', 'Mango'], true)) {
            $score += 0.10;
        }

        if (!empty($weather['temperature'])) {
            if (preg_match('/(\d+)-(\d+)/', $profile['optimal_temp'], $matches)) {
                $minTemp = (float)$matches[1];
                $maxTemp = (float)$matches[2];
                $midTemp = ($minTemp + $maxTemp) / 2.0;
                $diff    = abs($weather['temperature'] - $midTemp);

                if ($diff <= 1) {
                    $score += 0.15;
                } elseif ($diff <= 2) {
                    $score += 0.08;
                } elseif ($diff <= 4) {
                    $score -= 0.05;
                } else {
                    $score -= 0.10;
                }
            }
        }

        if (!empty($weather['rain'])) {
            $needs = strtolower($profile['water_needs']);
            if ($weather['rain'] === 'yes' && in_array($needs, ['high', 'very high'], true)) {
                $score += 0.06;
            } elseif ($weather['rain'] === 'no' && $needs === 'low') {
                $score += 0.04;
            }
        }

        if ($profile['crop_name'] === 'Tomato' && strpos(strtolower((string)$field['notes']), 'shady') !== false) {
            $score -= 0.08;
        }

        $score = max(min($score, 0.98), 0.05);

        $recommendations[] = [
            'crop_name'      => $profile['crop_name'],
            'score'          => round($score, 2),
            'optimal_temp'   => $profile['optimal_temp'],
            'water_needs'    => $profile['water_needs'],
            'season'         => ucfirst($profile['season']),
            'season_hint'    => $profile['season'] === 'wet'
                ? 'Performs best during the wet season (May–Oct).'
                : 'Performs best during the dry season (Nov–Apr).',
            'duration_days'  => $profile['duration_days'],
            'notes'          => $profile['notes'],
            'field_match'    => ucfirst($type ?: 'general'),
            'temperature_gap'=> isset($midTemp) && isset($weather['temperature'])
                ? round($weather['temperature'] - $midTemp, 1)
                : null
        ];
    }

    usort($recommendations, static fn($a, $b) => $b['score'] <=> $a['score']);
    $top = array_slice($recommendations, 0, 3);

    echo json_encode([
        'success'         => true,
        'field_id'        => $fieldId,
        'field_name'      => $field['name'],
        'season_context'  => ucfirst($season) . ' season',
        'weather'         => $weather,
        'recommendations' => $top
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'recommendations' => []
    ]);
}