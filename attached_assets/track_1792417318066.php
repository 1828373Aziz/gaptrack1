<?php
require_once 'config/db.php';

$shipment_id = 1;
$sql = "SELECT * FROM tracking_updates WHERE shipment_id = $shipment_id ORDER BY timestamp ASC";
$result = $conn->query($sql);

$points = [];
while ($row = $result->fetch_assoc()) {
    $points[] = ['lat' => $row['lat'], 'lng' => $row['lng'], 'status' => $row['connection_status']];
}
$points_json = json_encode($points);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تتبع الشحنة</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 0; }
        h1 { text-align: center; color: #2F5496; padding: 15px; }
        #map { height: 600px; width: 100%; }
    </style>
</head>
<body>
    <h1>تتبع الشحنة رقم <?= $shipment_id ?> — الرياض إلى جدة</h1>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const points = <?= $points_json ?>;

        const map = L.map('map').setView([points[0].lat, points[0].lng], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const latlngs = points.map(p => [p.lat, p.lng]);
        L.polyline(latlngs, { color: 'blue' }).addTo(map);

        points.forEach((p, i) => {
            L.marker([p.lat, p.lng])
                .addTo(map)
                .bindPopup(`نقطة ${i + 1} — الحالة: ${p.status}`);
        });

        map.fitBounds(latlngs);
    </script>
</body>
</html>