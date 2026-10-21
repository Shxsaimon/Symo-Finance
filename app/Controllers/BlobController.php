<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class BlobController extends Controller
{
    public function avatar(int|string $id): void
    {
        AuthHelper::requireLogin();
        $targetUserId = (int) $id;

        if ($targetUserId !== AuthHelper::id() && !AuthHelper::isDev()) {
            LoggerHelper::security('ACCESS_DENIED_AVATAR_IDOR', [
                'target_user_id' => $targetUserId,
            ]);
            http_response_code(403);
            exit;
        }

        $user = (new User())->find($targetUserId);
        $this->serve($user['avatar'] ?? null, 'avatar');
    }

    public function logo(): void
    {
        AuthHelper::requireLogin();

        $logo = (new Setting())->get('logotipo_aplicacao');
        $this->serve(is_string($logo) ? $logo : null, 'logo');
    }

    public function feedbackImage(int|string $id, int|string $index): void
    {
        AuthHelper::requireDev();

        $imageUrl = (new Feedback())->getImageUrl((int) $id, (int) $index);
        $this->serve($imageUrl, 'feedback');
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
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        header('Content-Length: ' . strlen($blob['data']));
        header('Cache-Control: private, no-store');
        echo $blob['data'];
        exit;
    }
}
