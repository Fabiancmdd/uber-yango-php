<?php
declare(strict_types=1);

class AdminController
{
    public function dashboard(array $params = []): void
    {
        require_role('admin');
        $stats = [
            'users'      => (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'passengers' => (int)db()->query("SELECT COUNT(*) FROM users WHERE role='passenger'")->fetchColumn(),
            'drivers'    => (int)db()->query("SELECT COUNT(*) FROM users WHERE role='driver'")->fetchColumn(),
            'rides'      => (int)db()->query("SELECT COUNT(*) FROM rides")->fetchColumn(),
            'completed'  => (int)db()->query("SELECT COUNT(*) FROM rides WHERE status='completed'")->fetchColumn(),
            'active'     => (int)db()->query("SELECT COUNT(*) FROM rides WHERE status IN ('requested','accepted','in_progress')")->fetchColumn(),
            'revenue'    => (float)db()->query("SELECT COALESCE(SUM(fare_final),0) FROM rides WHERE status='completed'")->fetchColumn(),
        ];
        render('admin/dashboard', ['stats' => $stats], 'Panel admin');
    }

    public function users(array $params = []): void
    {
        require_role('admin');
        $users = db()->query(
            "SELECT u.*,
                    p.rating_avg AS p_rating, p.total_rides AS p_rides,
                    d.rating_avg AS d_rating, d.total_rides AS d_rides, d.is_online
             FROM users u
             LEFT JOIN passengers p ON p.user_id = u.id
             LEFT JOIN drivers    d ON d.user_id = u.id
             ORDER BY u.id DESC"
        )->fetchAll();
        render('admin/users', ['users' => $users], 'Usuarios');
    }

    public function toggleUser(array $params): void
    {
        csrf_check();
        require_role('admin');
        $id = (int)$params['id'];
        db()->prepare(
            "UPDATE users SET status = CASE status WHEN 'active' THEN 'suspended' ELSE 'active' END
              WHERE id = ?"
        )->execute([$id]);
        flash('success', 'Estado del usuario actualizado.');
        redirect(url('/admin/users'));
    }

    public function rides(array $params = []): void
    {
        require_role('admin');
        $rides = db()->query(
            "SELECT r.*,
                    up.full_name AS passenger_name,
                    ud.full_name AS driver_name
             FROM rides r
             JOIN passengers p  ON p.id = r.passenger_id
             JOIN users      up ON up.id = p.user_id
             LEFT JOIN drivers d  ON d.id = r.driver_id
             LEFT JOIN users   ud ON ud.id = d.user_id
             ORDER BY r.id DESC LIMIT 200"
        )->fetchAll();
        render('admin/rides', ['rides' => $rides], 'Viajes');
    }

    public function fares(array $params = []): void
    {
        require_role('admin');
        $fares = db()->query("SELECT * FROM fare_settings ORDER BY category")->fetchAll();
        render('admin/fares', ['fares' => $fares], 'Tarifas');
    }

    public function updateFares(array $params = []): void
    {
        csrf_check();
        require_role('admin');
        $rows = $_POST['fare'] ?? [];
        if (!is_array($rows)) {
            redirect(url('/admin/fares'));
        }
        $upd = db()->prepare(
            "UPDATE fare_settings SET base_fare=?, per_km=?, per_min=?, min_fare=?, currency=?
              WHERE category=?"
        );
        foreach ($rows as $cat => $r) {
            if (!is_array($r)) continue;
            $upd->execute([
                (float)($r['base_fare'] ?? 0),
                (float)($r['per_km']    ?? 0),
                (float)($r['per_min']   ?? 0),
                (float)($r['min_fare']  ?? 0),
                substr((string)($r['currency'] ?? 'USD'), 0, 8),
                $cat,
            ]);
        }
        flash('success', 'Tarifas actualizadas.');
        redirect(url('/admin/fares'));
    }
}
