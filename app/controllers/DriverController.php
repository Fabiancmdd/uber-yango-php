<?php
declare(strict_types=1);

class DriverController
{
    private function ensureDriver(): array
    {
        $u = require_role('driver');
        $did = driver_id_for_user((int)$u['id']);
        if (!$did) {
            http_response_code(500);
            die('Perfil de conductor no encontrado.');
        }
        $vstmt = db()->prepare("SELECT * FROM vehicles WHERE driver_id = ? ORDER BY id LIMIT 1");
        $vstmt->execute([$did]);
        return [
            'user'      => $u,
            'driver_id' => $did,
            'vehicle'   => $vstmt->fetch() ?: null,
        ];
    }

    public function dashboard(array $params = []): void
    {
        $ctx = $this->ensureDriver();
        $did = $ctx['driver_id'];

        $dr = db()->prepare("SELECT * FROM drivers WHERE id = ?");
        $dr->execute([$did]);
        $driver = $dr->fetch();

        // Viaje activo del conductor
        $active = db()->prepare(
            "SELECT r.*, u.full_name AS passenger_name, u.phone AS passenger_phone,
                    p.rating_avg AS passenger_rating
             FROM rides r
             JOIN passengers p ON p.id = r.passenger_id
             JOIN users      u ON u.id = p.user_id
             WHERE r.driver_id = ? AND r.status IN ('accepted','in_progress')
             ORDER BY r.id DESC LIMIT 1"
        );
        $active->execute([$did]);
        $activeRide = $active->fetch() ?: null;

        $queue = [];
        if (!$activeRide && (int)$driver['is_online'] === 1) {
            // Cola de viajes solicitados sin conductor (orden por cercanía si tenemos coords)
            $cat = db()->prepare("SELECT category FROM vehicles WHERE driver_id = ? LIMIT 1");
            $cat->execute([$did]);
            $vehicleCat = (string)($cat->fetchColumn() ?: 'economy');

            $q = db()->prepare(
                "SELECT r.*, u.full_name AS passenger_name, p.rating_avg AS passenger_rating
                 FROM rides r
                 JOIN passengers p ON p.id = r.passenger_id
                 JOIN users      u ON u.id = p.user_id
                 WHERE r.status = 'requested' AND r.driver_id IS NULL AND r.category = ?
                 ORDER BY r.requested_at ASC LIMIT 10"
            );
            $q->execute([$vehicleCat]);
            $queue = $q->fetchAll();
        }

        $recent = db()->prepare(
            "SELECT id, status, fare_final, requested_at, origin_address, destination_address
             FROM rides WHERE driver_id = ? AND status IN ('completed','cancelled')
             ORDER BY id DESC LIMIT 5"
        );
        $recent->execute([$did]);

        render('driver/dashboard', [
            'user'       => $ctx['user'],
            'driver'     => $driver,
            'vehicle'    => $ctx['vehicle'],
            'activeRide' => $activeRide,
            'queue'      => $queue,
            'recent'     => $recent->fetchAll(),
        ], 'Panel de conductor');
    }

    public function toggleOnline(array $params = []): void
    {
        csrf_check();
        $ctx = $this->ensureDriver();
        $online = (int)input('online', '0') === 1 ? 1 : 0;
        db()->prepare("UPDATE drivers SET is_online = ? WHERE id = ?")
            ->execute([$online, $ctx['driver_id']]);
        flash('success', $online ? 'Estás en línea.' : 'Te has desconectado.');
        redirect(url('/driver'));
    }

    public function accept(array $params): void
    {
        csrf_check();
        $ctx = $this->ensureDriver();
        if (!$ctx['vehicle']) {
            flash('error', 'Necesitas registrar un vehículo antes de aceptar viajes.');
            redirect(url('/driver'));
        }
        $rideId = (int)$params['id'];

        // Verificar que no tenga otro viaje activo
        $busy = db()->prepare(
            "SELECT id FROM rides WHERE driver_id = ? AND status IN ('accepted','in_progress')"
        );
        $busy->execute([$ctx['driver_id']]);
        if ($busy->fetchColumn()) {
            flash('error', 'Ya tienes un viaje en curso.');
            redirect(url('/driver'));
        }

        $upd = db()->prepare(
            "UPDATE rides SET status='accepted', driver_id=?, vehicle_id=?, accepted_at=NOW()
              WHERE id=? AND status='requested' AND driver_id IS NULL"
        );
        $upd->execute([$ctx['driver_id'], $ctx['vehicle']['id'], $rideId]);
        if ($upd->rowCount() === 0) {
            flash('error', 'El viaje ya fue tomado por otro conductor.');
        } else {
            flash('success', 'Viaje aceptado.');
        }
        redirect(url('/driver'));
    }

    public function start(array $params): void
    {
        csrf_check();
        $ctx = $this->ensureDriver();
        $rideId = (int)$params['id'];
        db()->prepare(
            "UPDATE rides SET status='in_progress', started_at=NOW()
              WHERE id=? AND driver_id=? AND status='accepted'"
        )->execute([$rideId, $ctx['driver_id']]);
        flash('success', 'Viaje iniciado.');
        redirect(url('/driver'));
    }

    public function complete(array $params): void
    {
        csrf_check();
        $ctx = $this->ensureDriver();
        $rideId = (int)$params['id'];

        // Recalcular tarifa final usando duración real si hubo started_at
        $rstmt = db()->prepare("SELECT * FROM rides WHERE id=? AND driver_id=? AND status='in_progress'");
        $rstmt->execute([$rideId, $ctx['driver_id']]);
        $ride = $rstmt->fetch();
        if (!$ride) {
            flash('error', 'No se puede completar este viaje.');
            redirect(url('/driver'));
        }

        $minutes = (float)$ride['duration_min'];
        if (!empty($ride['started_at'])) {
            $diff = max(0, time() - strtotime($ride['started_at']));
            $minutes = max($minutes, round($diff / 60, 2));
        }

        $fare = calculate_fare((string)$ride['category'], (float)$ride['distance_km'], $minutes);

        db()->prepare(
            "UPDATE rides SET status='completed', completed_at=NOW(),
                    duration_min=?, fare_final=? WHERE id=?"
        )->execute([$minutes, $fare['fare'], $rideId]);

        // Actualizar estadísticas
        db()->prepare(
            "UPDATE drivers
                SET total_rides   = total_rides + 1,
                    total_earnings = total_earnings + ?
              WHERE id = ?"
        )->execute([$fare['fare'], $ctx['driver_id']]);

        db()->prepare(
            "UPDATE passengers SET total_rides = total_rides + 1 WHERE id = ?"
        )->execute([$ride['passenger_id']]);

        flash('success', 'Viaje completado. Tarifa final: ' . $fare['fare']);
        redirect(url('/driver'));
    }

    public function rate(array $params): void
    {
        csrf_check();
        $ctx = $this->ensureDriver();
        $id  = (int)$params['id'];
        $stars = max(1, min(5, (int)input('stars', '5')));
        $comment = trim((string)input('comment', ''));

        $check = db()->prepare(
            "SELECT passenger_id FROM rides WHERE id=? AND driver_id=? AND status='completed'"
        );
        $check->execute([$id, $ctx['driver_id']]);
        $row = $check->fetch();
        if (!$row) {
            flash('error', 'No puedes calificar este viaje.');
            redirect(url('/driver'));
        }

        try {
            db()->prepare(
                "INSERT INTO ratings (ride_id, rater_role, stars, comment)
                 VALUES (?, 'driver', ?, ?)"
            )->execute([$id, $stars, $comment ?: null]);
        } catch (PDOException $e) {
            flash('error', 'Ya calificaste este viaje.');
            redirect(url('/driver'));
        }

        $avgStmt = db()->prepare(
            "SELECT AVG(rt.stars) FROM ratings rt
              JOIN rides ri ON ri.id = rt.ride_id
              WHERE ri.passenger_id = ? AND rt.rater_role = 'driver'"
        );
        $avgStmt->execute([$row['passenger_id']]);
        $avg = (float)$avgStmt->fetchColumn();
        db()->prepare("UPDATE passengers SET rating_avg = ? WHERE id = ?")
            ->execute([round($avg, 2), $row['passenger_id']]);

        flash('success', 'Gracias por calificar.');
        redirect(url('/driver'));
    }

    public function earnings(array $params = []): void
    {
        $ctx = $this->ensureDriver();
        $did = $ctx['driver_id'];

        $sum = db()->prepare(
            "SELECT
               COUNT(*) AS total_completed,
               COALESCE(SUM(fare_final),0) AS total_earnings,
               COALESCE(AVG(fare_final),0) AS avg_fare
             FROM rides WHERE driver_id = ? AND status = 'completed'"
        );
        $sum->execute([$did]);
        $stats = $sum->fetch();

        $byDay = db()->prepare(
            "SELECT DATE(completed_at) AS day,
                    COUNT(*) AS rides,
                    COALESCE(SUM(fare_final),0) AS total
             FROM rides WHERE driver_id = ? AND status = 'completed'
             GROUP BY DATE(completed_at) ORDER BY day DESC LIMIT 30"
        );
        $byDay->execute([$did]);

        render('driver/earnings', [
            'stats' => $stats,
            'byDay' => $byDay->fetchAll(),
        ], 'Mis ganancias');
    }
}
