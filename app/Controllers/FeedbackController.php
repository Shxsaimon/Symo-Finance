<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class FeedbackController extends Controller
{
    private const MAX_IMAGENS = 5;

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

        $imagensUrls = $this->processarImagensAnexadas();

        $this->feedbackModel->registrar((int) AuthHelper::id(), $texto, $tela, $tipo, $imagensUrls);

        FlashHelper::set('success', 'Feedback enviado. Obrigado por ajudar a evoluir o Symo.');
        $this->redirect('/dashboard');
    }

    /**
     * Valida e envia ao Blob as imagens anexadas ao feedback, interrompendo o fluxo em caso de falha.
     *
     * @return array<int, string>
     */
    private function processarImagensAnexadas(): array
    {
        if (empty($_FILES['imagens']) || empty($_FILES['imagens']['name'][0])) {
            return [];
        }

        $arquivos = $_FILES['imagens'];
        $total = count($arquivos['name']);

        if ($total > self::MAX_IMAGENS) {
            FlashHelper::set('error', 'É possível anexar no máximo ' . self::MAX_IMAGENS . ' imagens por feedback.');
            $this->redirect('/dashboard');
        }

        $urls = [];
        for ($i = 0; $i < $total; $i++) {
            if ((int) $arquivos['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $arquivoUnico = [
                'name'     => $arquivos['name'][$i],
                'type'     => $arquivos['type'][$i],
                'tmp_name' => $arquivos['tmp_name'][$i],
                'error'    => $arquivos['error'][$i],
                'size'     => $arquivos['size'][$i],
            ];

            $resultado = UploadHelper::uploadImage($arquivoUnico, 'feedback');
            if (!$resultado['success']) {
                FlashHelper::set('error', 'Uma das imagens não pôde ser anexada: ' . $resultado['error']);
                $this->redirect('/dashboard');
            }

            $urls[] = $resultado['filename'];
        }

        return $urls;
    }
}