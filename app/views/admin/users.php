<?php /** @var array $users */ ?>
<h1>Usuarios</h1>
<table class="table">
    <thead><tr>
        <th>#</th><th>Nombre</th><th>Email</th><th>Rol</th>
        <th>Rating</th><th>Viajes</th><th>Estado</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?>
                <?php if ($u['role']==='driver'): ?>
                    · <?= ((int)$u['is_online']) ? '🟢' : '⚪' ?>
                <?php endif; ?>
            </td>
            <td>
                <?= e($u['role']==='driver'
                    ? number_format((float)($u['d_rating'] ?? 0), 2)
                    : ($u['role']==='passenger' ? number_format((float)($u['p_rating'] ?? 0), 2) : '—')) ?>
            </td>
            <td>
                <?= e((string)($u['role']==='driver'
                    ? ($u['d_rides'] ?? 0)
                    : ($u['role']==='passenger' ? ($u['p_rides'] ?? 0) : '—'))) ?>
            </td>
            <td><span class="status status-<?= e($u['status']) ?>"><?= e($u['status']) ?></span></td>
            <td>
                <?php if ($u['role'] !== 'admin'): ?>
                    <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/toggle"
                          onsubmit="return confirm('¿Cambiar estado?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-sm">
                            <?= $u['status']==='active' ? 'Suspender' : 'Activar' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
