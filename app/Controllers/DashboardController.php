<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador do Dashboard Financeiro (DashboardController)
 *
 * Exibe a visão panorâmica, indicadores rápidos e layout principal da aplicação.
 */
class DashboardController extends Controller
{
    /**
     * Exibe o painel principal do usuário autenticado no layout mestre.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $this->render('dashboard/index', [
            'title' => 'Dashboard Financeiro — Symo',
            'user'  => AuthHelper::user(),
        ], 'layouts/main');
    }
}
