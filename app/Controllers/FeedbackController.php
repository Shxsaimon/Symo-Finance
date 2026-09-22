<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class FeedbackController extends Controller
{
    private Feedback $feedbackModel;

    public function __construct()
    {
        $this->feedbackModel = new Feedback();
    }

    public function store(): void
    {
        AuthHelper::requireLogin();

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de validação expirado ou inválido. Tente enviar seu feedback novamente.');
            $this->redirect('/dashboard');
        }

        $texto = trim((string) ($_POST['texto'] ?? ''));
        $tela = trim((string) ($_POST['tela'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? ''));

        if (mb_strlen($texto) < 10 || mb_strlen($texto) > 3000) {
            FlashHelper::set('error', 'O feedback deve ter entre 10 e 3.000 caracteres.');
            $this->redirect('/dashboard');
        }

        if (!Feedback::telaPermitida($tela) || !Feedback::tipoPermitido($tipo)) {
            FlashHelper::set('error', 'Selecione uma tela e um tipo de feedback válidos.');
            $this->redirect('/dashboard');
        }

        $this->feedbackModel->registrar((int) AuthHelper::id(), $texto, $tela, $tipo);
        FlashHelper::set('success', 'Feedback enviado. Obrigado por ajudar a evoluir o Symo.');
        $this->redirect('/dashboard');
    }
}