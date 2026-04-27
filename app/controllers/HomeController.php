<?php
declare(strict_types=1);

class HomeController
{
    public function index(array $params = []): void
    {
        $u = current_user();
        if ($u) {
            redirect(url('/' . $u['role']));
        }
        render('home', [], 'Inicio');
    }
}
