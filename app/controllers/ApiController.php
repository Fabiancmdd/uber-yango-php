<?php
declare(strict_types=1);

class ApiController
{
    public function estimate(array $params = []): void
    {
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        $oLat = (float)($payload['origin_lat']      ?? 0);
        $oLng = (float)($payload['origin_lng']      ?? 0);
        $dLat = (float)($payload['destination_lat'] ?? 0);
        $dLng = (float)($payload['destination_lng'] ?? 0);
        $cat  = (string)($payload['category']       ?? 'economy');

        if (!$oLat || !$oLng || !$dLat || !$dLng) {
            json_response(['error' => 'coordenadas inválidas'], 422);
        }
        if (!in_array($cat, ['economy','comfort','xl'], true)) {
            $cat = 'economy';
        }

        $km   = haversine_km($oLat, $oLng, $dLat, $dLng);
        $mins = estimate_duration_min($km);
        $fare = calculate_fare($cat, $km, $mins);

        json_response([
            'distance_km'   => round($km, 3),
            'duration_min'  => $mins,
            'fare'          => $fare['fare'],
            'currency'      => $fare['currency'],
            'breakdown'     => $fare['breakdown'],
        ]);
    }

    public function rideStatus(array $params): void
    {
        $u = require_login();
        $id = (int)$params['id'];

        $stmt = db()->prepare(
            "SELECT r.id, r.status, r.fare_final, r.fare_estimated,
                    r.driver_id, r.passenger_id,
                    u.full_name AS driver_name, v.brand, v.model, v.plate
             FROM rides r
             LEFT JOIN drivers  d ON d.id = r.driver_id
             LEFT JOIN users    u ON u.id = d.user_id
             LEFT JOIN vehicles v ON v.id = r.vehicle_id
             WHERE r.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $ride = $stmt->fetch();
        if (!$ride) {
            json_response(['error' => 'no encontrado'], 404);
        }

        // Autorización
        if ($u['role'] === 'passenger') {
            $pid = passenger_id_for_user((int)$u['id']);
            if ($pid !== (int)$ride['passenger_id']) {
                json_response(['error' => 'prohibido'], 403);
            }
        } elseif ($u['role'] === 'driver') {
            $did = driver_id_for_user((int)$u['id']);
            if ($did !== (int)$ride['driver_id']) {
                json_response(['error' => 'prohibido'], 403);
            }
        }

        json_response($ride);
    }

    public function driverQueue(array $params = []): void
    {
        $u = require_role('driver');
        $did = driver_id_for_user((int)$u['id']);
        $cat = db()->prepare("SELECT category FROM vehicles WHERE driver_id = ? LIMIT 1");
        $cat->execute([$did]);
        $vehicleCat = (string)($cat->fetchColumn() ?: 'economy');

        $stmt = db()->prepare(
            "SELECT r.id, r.origin_address, r.destination_address,
                    r.distance_km, r.fare_estimated, r.requested_at, r.category
             FROM rides r
             WHERE r.status = 'requested' AND r.driver_id IS NULL AND r.category = ?
             ORDER BY r.requested_at ASC LIMIT 10"
        );
        $stmt->execute([$vehicleCat]);
        json_response(['queue' => $stmt->fetchAll()]);
    }
}
