<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Riyadh');

const GAPTRACK_DATA_FILE = __DIR__ . '/../data/gaptrack-demo.json';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page_url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

function route_cities(): array
{
    return [
        'الرياض' => [24.7136, 46.6753],
        'جدة' => [21.4858, 39.1925],
        'الدمام' => [26.4207, 50.0888],
        'مكة المكرمة' => [21.3891, 39.8579],
        'المدينة المنورة' => [24.5247, 39.5692],
        'الطائف' => [21.2703, 40.4158],
        'تبوك' => [28.3838, 36.5550],
        'أبها' => [18.2164, 42.5053],
        'نجران' => [17.4933, 44.1277],
        'حائل' => [27.5114, 41.6900],
        'بريدة' => [26.3260, 43.9750],
        'عنيزة' => [26.0843, 43.9936],
    ];
}

function sample_data(): array
{
    $now = time();
    $at = static fn(int $hoursAgo): string => date(DATE_ATOM, $now - ($hoursAgo * 3600));
    return [
        'demo' => true,
        'next_shipment_id' => 4,
        'next_zone_id' => 3,
        'zones' => [
            [
                'id' => 1,
                'name' => 'منطقة تجريبية — وسط المملكة',
                'lat_min' => 22.8,
                'lat_max' => 23.8,
                'lng_min' => 42.2,
                'lng_max' => 43.6,
                'expected_gap_minutes' => 35,
            ],
            [
                'id' => 2,
                'name' => 'منطقة تجريبية — شمال المملكة',
                'lat_min' => 27.0,
                'lat_max' => 28.0,
                'lng_min' => 41.0,
                'lng_max' => 42.3,
                'expected_gap_minutes' => 25,
            ],
        ],
        'shipments' => [
            [
                'id' => 1,
                'tracking_code' => 'GT-DEMO-0001',
                'origin' => 'الرياض',
                'destination' => 'جدة',
                'type' => 'land',
                'status' => 'in_transit',
                'created_at' => $at(6),
                'points' => [
                    ['id' => 1, 'lat' => 24.7136, 'lng' => 46.6753, 'time' => $at(6)],
                    ['id' => 2, 'lat' => 24.0000, 'lng' => 45.0000, 'time' => $at(5)],
                    ['id' => 3, 'lat' => 23.2000, 'lng' => 42.9000, 'time' => $at(4)],
                    ['id' => 4, 'lat' => 22.2000, 'lng' => 41.0000, 'time' => $at(3)],
                    ['id' => 5, 'lat' => 21.4858, 'lng' => 39.1925, 'time' => $at(2)],
                ],
            ],
            [
                'id' => 2,
                'tracking_code' => 'GT-DEMO-0002',
                'origin' => 'الرياض',
                'destination' => 'حائل',
                'type' => 'land',
                'status' => 'in_transit',
                'created_at' => $at(10),
                'points' => [
                    ['id' => 6, 'lat' => 24.7136, 'lng' => 46.6753, 'time' => $at(10)],
                    ['id' => 7, 'lat' => 25.6000, 'lng' => 45.6000, 'time' => $at(9)],
                    ['id' => 8, 'lat' => 26.4000, 'lng' => 44.8000, 'time' => $at(5)],
                    ['id' => 9, 'lat' => 27.5114, 'lng' => 41.6900, 'time' => $at(4)],
                ],
            ],
            [
                'id' => 3,
                'tracking_code' => 'GT-DEMO-0003',
                'origin' => 'جدة',
                'destination' => 'المدينة المنورة',
                'type' => 'land',
                'status' => 'in_transit',
                'created_at' => $at(3),
                'points' => [
                    ['id' => 10, 'lat' => 21.4858, 'lng' => 39.1925, 'time' => $at(3)],
                    ['id' => 11, 'lat' => 22.2000, 'lng' => 39.3000, 'time' => $at(2)],
                    ['id' => 12, 'lat' => 23.4000, 'lng' => 39.4000, 'time' => $at(1)],
                    ['id' => 13, 'lat' => 24.5247, 'lng' => 39.5692, 'time' => $at(0)],
                ],
            ],
        ],
    ];
}

function save_data(array $data): void
{
    $directory = dirname(GAPTRACK_DATA_FILE);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('تعذر إنشاء مجلد بيانات العرض.');
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('تعذر تجهيز بيانات العرض للحفظ.');
    }

    $temporaryFile = GAPTRACK_DATA_FILE . '.tmp';
    if (file_put_contents($temporaryFile, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('تعذر حفظ بيانات العرض.');
    }
    if (!rename($temporaryFile, GAPTRACK_DATA_FILE)) {
        @unlink($temporaryFile);
        throw new RuntimeException('تعذر تثبيت ملف بيانات العرض.');
    }
}

function load_data(): array
{
    if (!is_file(GAPTRACK_DATA_FILE)) {
        $initial = sample_data();
        save_data($initial);
        return $initial;
    }

    $contents = file_get_contents(GAPTRACK_DATA_FILE);
    $data = $contents === false ? null : json_decode($contents, true);
    if (!is_array($data) || !isset($data['shipments'], $data['zones'])) {
        throw new RuntimeException('ملف بيانات العرض غير صالح. لا يمكن تحميل الرحلات بأمان.');
    }
    return $data;
}

function save_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verify_csrf_token(mixed $submitted): bool
{
    return is_string($submitted)
        && isset($_SESSION['csrf_token'])
        && hash_equals((string)$_SESSION['csrf_token'], $submitted);
}

function point_in_zone(float $lat, float $lng, array $zone): bool
{
    return $lat >= (float)$zone['lat_min']
        && $lat <= (float)$zone['lat_max']
        && $lng >= (float)$zone['lng_min']
        && $lng <= (float)$zone['lng_max'];
}

function classify_points(array $shipment, array $zones): array
{
    $points = $shipment['points'] ?? [];
    usort($points, static fn(array $a, array $b): int => strtotime($a['time']) <=> strtotime($b['time']));
    $previousTime = null;
    $result = [];

    foreach ($points as $point) {
        $pointTime = strtotime($point['time']);
        $gapMinutes = $previousTime === null ? 0 : ($pointTime - $previousTime) / 60;
        $zone = null;
        foreach ($zones as $candidate) {
            if (point_in_zone((float)$point['lat'], (float)$point['lng'], $candidate)) {
                $zone = $candidate;
                break;
            }
        }

        $classification = 'connected';
        $note = null;
        if ($zone !== null) {
            $classification = 'known_gap';
            $note = 'منطقة ضعف تغطية معروفة: ' . $zone['name'];
        } elseif ($gapMinutes > 90) {
            $classification = 'abnormal_gap';
            $note = 'انقطاع غير متوقع لمدة ' . (int)round($gapMinutes) . ' دقيقة خارج المناطق المسجلة';
        }

        $point['classification'] = $classification;
        $point['note'] = $note;
        $point['gap_minutes'] = (int)round($gapMinutes);
        $result[] = $point;
        $previousTime = $pointTime;
    }
    return $result;
}

function shipment_health(array $shipment, array $zones): string
{
    $points = classify_points($shipment, $zones);
    foreach ($points as $point) {
        if ($point['classification'] === 'abnormal_gap') {
            return 'abnormal_gap';
        }
    }
    foreach ($points as $point) {
        if ($point['classification'] === 'known_gap') {
            return 'known_gap';
        }
    }
    return 'connected';
}

function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $toRadians = static fn(float $degrees): float => $degrees * M_PI / 180;
    $dLat = $toRadians($lat2 - $lat1);
    $dLng = $toRadians($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
        + cos($toRadians($lat1)) * cos($toRadians($lat2)) * sin($dLng / 2) ** 2;
    return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function predictive_alert(array $shipment, array $zones): ?array
{
    $points = classify_points($shipment, $zones);
    $lastPoint = end($points);
    if (!$lastPoint || $lastPoint['classification'] !== 'connected' || !$zones) {
        return null;
    }

    $nearest = null;
    foreach ($zones as $zone) {
        $distance = haversine_km(
            (float)$lastPoint['lat'],
            (float)$lastPoint['lng'],
            ((float)$zone['lat_min'] + (float)$zone['lat_max']) / 2,
            ((float)$zone['lng_min'] + (float)$zone['lng_max']) / 2,
        );
        if ($nearest === null || $distance < $nearest['distance_km']) {
            $nearest = ['zone' => $zone, 'distance_km' => $distance];
        }
    }
    if ($nearest === null || $nearest['distance_km'] >= 150) {
        return null;
    }

    return [
        'zone_name' => $nearest['zone']['name'],
        'distance_km' => (int)round($nearest['distance_km']),
        'eta_minutes' => (int)round(($nearest['distance_km'] / 70) * 60),
        'timestamp' => $lastPoint['time'],
    ];
}

function format_time(string $value, string $format = 'Y/m/d — H:i'): string
{
    try {
        return (new DateTimeImmutable($value))->format($format);
    } catch (Throwable) {
        return $value;
    }
}

function arabic_today(): string
{
    if (class_exists(IntlDateFormatter::class)) {
        $formatter = new IntlDateFormatter(
            'ar_SA@calendar=gregorian',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Asia/Riyadh',
            IntlDateFormatter::GREGORIAN,
            'EEEE، d MMMM yyyy',
        );
        $formatted = $formatter->format(new DateTimeImmutable('now', new DateTimeZone('Asia/Riyadh')));
        if (is_string($formatted)) {
            return $formatted;
        }
    }
    return date('Y/m/d');
}

function classification_label(string $classification): string
{
    return match ($classification) {
        'known_gap' => 'فجوة متوقعة',
        'abnormal_gap' => 'انقطاع مقلق',
        default => 'متصل',
    };
}

function classification_class(string $classification): string
{
    return match ($classification) {
        'known_gap' => 'warning',
        'abnormal_gap' => 'danger',
        default => 'success',
    };
}

function find_shipment(array $data, int $id): ?array
{
    foreach ($data['shipments'] as $shipment) {
        if ((int)$shipment['id'] === $id) {
            return $shipment;
        }
    }
    return null;
}

function geo_to_svg(float $lat, float $lng): array
{
    return [
        'x' => 42 + (($lng - 34) / 22) * 870,
        'y' => 30 + ((33 - $lat) / 18) * 520,
    ];
}

function saudi_outline_points(): string
{
    $outline = [
        [32.1, 38.8], [30.7, 39.0], [29.5, 38.2], [28.3, 36.7], [26.3, 36.3],
        [24.1, 37.2], [22.4, 38.0], [21.0, 38.5], [19.4, 39.4], [18.2, 40.6],
        [17.3, 41.7], [16.4, 42.4], [16.5, 44.1], [17.0, 45.5], [17.2, 47.4],
        [18.1, 48.4], [18.7, 49.4], [20.1, 50.6], [21.4, 52.5], [22.5, 54.0],
        [23.7, 55.0], [24.5, 54.8], [25.4, 52.7], [26.2, 50.9], [27.1, 49.9],
        [28.5, 49.4], [29.5, 48.5], [30.0, 47.7], [29.3, 46.5], [29.0, 44.4],
        [28.3, 42.7], [28.4, 40.7], [29.5, 39.5],
    ];
    return implode(' ', array_map(static function (array $coordinate): string {
        $point = geo_to_svg((float)$coordinate[0], (float)$coordinate[1]);
        return $point['x'] . ',' . $point['y'];
    }, $outline));
}
