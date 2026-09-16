<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class BlobController extends Controller
{
    public function avatar(int|string $id): void
    {
        AuthHelper::requireLogin();

        $user = (new User())->find((int) $id);
        $this->serve($user['avatar'] ?? null, 'avatar');
    }

    public function logo(): void
    {
        AuthHelper::requireLogin();

        $logo = (new Setting())->get('logotipo_aplicacao');
        $this->serve(is_string($logo) ? $logo : null, 'logo');
    }

    private function serve(?string $url, string $resource): void
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(404);
            exit;
        }

        $blob = UploadHelper::downloadBlob($url);
        if ($blob === null) {
            LoggerHelper::security('BLOB_DOWNLOAD_FAILED', ['resource' => $resource]);
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $blob['contentType']);
        header('Content-Length: ' . strlen($blob['data']));
        header('Cache-Control: private, no-store');
        echo $blob['data'];
        exit;
    }
}
