<?php
declare(strict_types=1);

class PassengerController
{
    private function ensurePassenger(): array
    {
        $u = require_role('passenger');
        $pid = passenger_id_for_user((int)$u['id']);
        if (!$pid) {
            http_response_code(500);
            die('Perfil de pasajero no encontrado.');
        }
        return ['user' => $u, 'passenger_id' => $pid];
    }

    public function dashboard(array $params = []): void
    {
        $ctx = $this->ensurePassenger();

        $active = db()->prepare(
            "SELECT r.*, u.full_name AS driver_name, v.brand, v.model, v.plate, v.color
             FROM rides r
             LEFT JOIN drivers d  ON d.id = r.driver_id
             LEFT JOIN users   u  ON u.id = d.user_id
             LEFT JOIN vehicles v ON v.id = r.vehicle_id
             WHERE r.passenger_id = ?
               AND r.status IN ('requested','accepted','in_progress')
             ORDER BY r.id DESC LIMIT 1"
        );
        $active->execute([$ctx['passenger_id']]);
        $activeRide = $active->fetch() ?: null;

        $recent = db()->prepare(
            "SELECT r.id, r.status, r.fare_final, r.fare_estimated, r.requested_at,
                    r.origin_address, r.destination_address
             FROM rides r WHERE r.passenger_id = ?
             ORDER BY r.id DESC LIMIT 5"
        );
        $recent->execute([$ctx['passenger_id']]);

        render('passenger/dashboard', [
            'user'       => $ctx['user'],
            'activeRide' => $activeRide,
            'recent'     => $recent->fetchAll(),
        ], 'Mi panel');
    }

    public function showRequest(array $params = []): void
    {
        $this->ensurePassenger();
        render('passenger/request', [], 'Solicitar viaje');
    }

    public function createRequest(array $params = []): void
    {
        csrf_check();
        $ctx = $this->ensurePassenger();

        $oAddr = (string)input('origin_address', '');
        $dAddr = (string)input('destination_address', '');
        $oLat  = (float)input('origin_lat', '0');
        $oLng  = (float)input('origin_lng', '0');
        $dLat  = (float)input('destination_lat', '0');
        $dLng  = (float)input('destination_lng', '0');
        $cat   = (string)input('category', 'economy');
        $pay   = (string)input('payment_method', 'cash');

        if (!in_array($cat, ['economy','comfort','xl'], true)) {
            $cat = 'economy';
        }
        if (!in_array($pay, ['cash','card'], true)) {
            $pay = 'cash';
        }

        if ($oAddr === '' || $dAddr === '' || !$oLat || !$oLng || !$dLat || !$dLng) {
            flash('error', 'Selecciona origen y destino en el mapa.');
            redirect(url('/passenger/request'));
        }

        // Bloquear si ya hay un viaje activo
        $busy = db()->prepare(
            "SELECT id FROM rides WHERE passenger_id = ?
              AND status IN ('requested','accepted','in_progress') LIMIT 1"
        );
        $busy->execute([$ctx['passenger_id']]);
        if ($busy->fetchColumn()) {
            flash('error', 'Ya tienes un viaje activo.');
            redirect(url('/passenger'));
        }

        $km   = haversine_km($oLat, $oLng, $dLat, $dLng);
        $mins = estimate_duration_min($km);
        $fare = calculate_fare($cat, $km, $mins);

        $stmt = db()->prepare(
            "INSERT INTO rides
                (passenger_id, origin_address, origin_lat, origin_lng,
                 destination_address, destination_lat, destination_lng,
                 distance_km, duration_min, category, fare_estimated, payment_method, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested')"
        );
        $stmt->execute([
            $ctx['passenger_id'], $oAddr, $oLat, $oLng,
            $dAddr, $dLat, $dLng,
            $km, $mins, $cat, $fare['fare'], $pay,
        ]);
        $rideId = (int)db()->lastInsertId();

        flash('success', 'Viaje solicitado. Buscando conductor...');
        redirect(url('/passenger/rides/' . $rideId));
    }

    public function showRide(array $params): void
    {
        $ctx = $this->ensurePassenger();
        $id  = (int)$params['id'];
        $stmt = db()->prepare(
            "SELECT r.*, u.full_name AS driver_name, u.phone AS driver_phone,
                    v.brand, v.model, v.plate, v.color, v.year, v.category AS vehicle_category,
                    d.rating_avg AS driver_rating
             FROM rides r
             LEFT JOIN drivers d  ON d.id = r.driver_id
             LEFT JOIN users   u  ON u.id = d.user_id
             LEFT JOIN vehicles v ON v.id = r.vehicle_id
             WHERE r.id = ? AND r.passenger_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $ctx['passenger_id']]);
        $ride = $stmt->fetch();
        if (!$ride) {
            http_response_code(404);
            die('Viaje no encontrado.');
        }

        $myRating = null;
        if ($ride['status'] === 'completed') {
            $rstmt = db()->prepare(
                "SELECT * FROM ratings WHERE ride_id = ? AND rater_role = 'passenger' LIMIT 1"
            );
            $rstmt->execute([$id]);
            $myRating = $rstmt->fetch() ?: null;
        }

        render('passenger/ride', [
            'ride'     => $ride,
            'myRating' => $myRating,
        ], 'Viaje #' . $id);
    }

    public function cancel(array $params): void
    {
        csrf_check();
        $ctx = $this->ensurePassenger();
        $id  = (int)$params['id'];
        $reason = trim((string)input('reason', 'Cancelado por el pasajero'));

        $upd = db()->prepare(
            "UPDATE rides SET status = 'cancelled', cancel_reason = ?
              WHERE id = ? AND passenger_id = ? AND status IN ('requested','accepted')"
        );
        $upd->execute([$reason, $id, $ctx['passenger_id']]);

        flash('success', 'Viaje cancelado.');
        redirect(url('/passenger'));
    }

    public function rate(array $params): void
    {
        csrf_check();
        $ctx = $this->ensurePassenger();
        $id  = (int)$params['id'];
        $stars = max(1, min(5, (int)input('stars', '5')));
        $comment = trim((string)input('comment', ''));

        $check = db()->prepare(
            "SELECT driver_id FROM rides WHERE id = ? AND passenger_id = ? AND status = 'completed'"
        );
        $check->execute([$id, $ctx['passenger_id']]);
        $row = $check->fetch();
        if (!$row) {
            flash('error', 'No puedes calificar este viaje.');
            redirect(url('/passenger'));
        }

        try {
            db()->prepare(
                "INSERT INTO ratings (ride_id, rater_role, stars, comment)
                 VALUES (?, 'passenger', ?, ?)"
            )->execute([$id, $stars, $comment ?: null]);
        } catch (PDOException $e) {
            flash('error', 'Ya calificaste este viaje.');
            redirect(url('/passenger/rides/' . $id));
        }

        // Recalcular promedio del conductor
        if ($row['driver_id']) {
            $avgStmt = db()->prepare(
                "SELECT AVG(rt.stars) FROM ratings rt
                  JOIN rides ri ON ri.id = rt.ride_id
                  WHERE ri.driver_id = ? AND rt.rater_role = 'passenger'"
            );
            $avgStmt->execute([$row['driver_id']]);
            $avg = (float)$avgStmt->fetchColumn();
            db()->prepare("UPDATE drivers SET rating_avg = ? WHERE id = ?")
                ->execute([round($avg, 2), $row['driver_id']]);
        }

        flash('success', 'Gracias por tu calificación.');
        redirect(url('/passenger/rides/' . $id));
    }

    public function history(array $params = []): void
    {
        $ctx = $this->ensurePassenger();
        $stmt = db()->prepare(
            "SELECT r.*, u.full_name AS driver_name
             FROM rides r
             LEFT JOIN drivers d ON d.id = r.driver_id
             LEFT JOIN users   u ON u.id = d.user_id
             WHERE r.passenger_id = ? ORDER BY r.id DESC"
        );
        $stmt->execute([$ctx['passenger_id']]);
        render('passenger/history', ['rides' => $stmt->fetchAll()], 'Mi historial');
    }
}
