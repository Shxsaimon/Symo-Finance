<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Upload Seguro de Arquivos (UploadHelper)
 *
 * Valida arquivos enviados verificando tamanho máximo (2 MB),
 * MIME-Type real e Magic Bytes via finfo_file, extensões permitidas
 * (.jpg, .jpeg, .png, .webp, .svg), gera nomes randômicos seguros com hash.
 */
class UploadHelper
{
    private const MAX_SIZE_BYTES = 2097152; // 2 MB em bytes
    
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    /**
     * Processa o upload seguro de uma imagem.
     *
     * @param array<string, mixed> $file Array do $_FILES['campo']
     * @param string $subfolder Subpasta de destino dentro de storage/uploads/ ('avatars' ou 'logos')
     * @param string|null $oldFilename Nome do arquivo anterior para exclusão (opcional)
     * @return array{success: bool, filename?: string, relative_path?: string, error?: string}
     */
    public static function uploadImage(array $file, string $subfolder, ?string $oldFilename = null): array
    {
        // 1. Verificação básica de erro no envio
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Parâmetros de arquivo inválidos.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => ['success' => false, 'error' => 'O arquivo enviado excede o limite máximo permitido de 2 MB.'],
                UPLOAD_ERR_NO_FILE => ['success' => false, 'error' => 'Nenhum arquivo foi enviado.'],
                default => ['success' => false, 'error' => 'Ocorreu um erro durante o upload do arquivo.'],
            };
        }

        // 2. Validação de tamanho máximo (2 MB)
        $fileSize = (int) ($file['size'] ?? 0);
        if ($fileSize <= 0 || $fileSize > self::MAX_SIZE_BYTES) {
            return ['success' => false, 'error' => 'O arquivo ultrapassa o tamanho máximo permitido de 2 MB.'];
        }

        // 3. Validação real de MIME-Type através de Magic Bytes (finfo_file)
        $tmpPath = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmpPath) && !file_exists($tmpPath)) {
            return ['success' => false, 'error' => 'Arquivo temporário de upload não encontrado.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return ['success' => false, 'error' => 'Não foi possível inicializar a validação MIME no servidor.'];
        }

        $detectedMime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if ($detectedMime === false || !array_key_exists($detectedMime, self::ALLOWED_MIME_TYPES)) {
            return ['success' => false, 'error' => 'Formato de arquivo inválido. Permitido apenas imagens JPEG, PNG, WebP ou SVG.'];
        }

        $extension = self::ALLOWED_MIME_TYPES[$detectedMime];

        if ($detectedMime === 'image/svg+xml') {
            $svgContent = file_get_contents($tmpPath);
            if (!is_string($svgContent) || preg_match('/<\s*(script|iframe|object|embed|foreignObject)\b|\bon[a-z]+\s*=|javascript:/i', $svgContent)) {
                return ['success' => false, 'error' => 'O arquivo SVG contém conteúdo não permitido.'];
            }
        }

        // 4. Sanitização e Geração de Nome Seguro com Hash Randômico
        $prefix = match ($subfolder) {
            'avatars' => 'avatar_',
            'feedback' => 'feedback_',
            default => 'logo_',
        };
        $randomHex = bin2hex(random_bytes(16));
        $newFilename = "{$prefix}{$randomHex}.{$extension}";

        $proxyUrl = rtrim((string) (getenv('BLOB_PROXY_URL') ?: (defined('BASE_URL') ? BASE_URL . '/api/blob' : '')), '/');
        $proxySecret = (string) (getenv('BLOB_PROXY_SECRET') ?: '');
        if ($proxyUrl === '' || $proxySecret === '') {
            return ['success' => false, 'error' => 'O proxy seguro do armazenamento da Vercel ainda não foi configurado.'];
        }

        $fileContent = file_get_contents($tmpPath);
        if (!is_string($fileContent)) {
            return ['success' => false, 'error' => 'Não foi possível ler a imagem enviada.'];
        }

        $responseData = self::requestProxy($proxyUrl, $proxySecret, [
            'action' => 'upload',
            'pathname' => $subfolder . '/' . $newFilename,
            'contentType' => $detectedMime,
            'data' => base64_encode($fileContent),
        ]);
        if (!is_array($responseData) || empty($responseData['url'])) {
            return ['success' => false, 'error' => 'Não foi possível salvar a imagem no armazenamento da Vercel.'];
        }

        if (!empty($oldFilename)) {
            self::deleteFile($subfolder, (string) $oldFilename);
        }

        return [
            'success' => true,
            'filename' => (string) $responseData['url'],
            'relative_path' => (string) $responseData['url'],
        ];
    }

    /**
     * Remove um arquivo de upload com segurança.
     *
     * @param string $subfolder
     * @param string $filename
     * @return bool
     */
    public static function deleteFile(string $subfolder, string $filename): bool
    {
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            $proxyUrl = rtrim((string) (getenv('BLOB_PROXY_URL') ?: (defined('BASE_URL') ? BASE_URL . '/api/blob' : '')), '/');
            $proxySecret = (string) (getenv('BLOB_PROXY_SECRET') ?: '');
            if ($proxyUrl === '' || $proxySecret === '') {
                return false;
            }

            return is_array(self::requestProxy($proxyUrl, $proxySecret, [
                'action' => 'delete',
                'url' => $filename,
            ]));
        }

        return false;
    }

    public static function downloadBlob(string $url): ?array
    {
        $proxyUrl = rtrim((string) (getenv('BLOB_PROXY_URL') ?: (defined('BASE_URL') ? BASE_URL . '/api/blob' : '')), '/');
        $proxySecret = (string) (getenv('BLOB_PROXY_SECRET') ?: '');
        if (!filter_var($url, FILTER_VALIDATE_URL) || $proxyUrl === '' || $proxySecret === '') {
            return null;
        }

        $result = self::requestProxy($proxyUrl, $proxySecret, [
            'action' => 'download',
            'url' => $url,
        ]);

        if (!is_array($result) || empty($result['data']) || empty($result['contentType'])) {
            return null;
        }

        $content = base64_decode((string) $result['data'], true);
        return $content === false ? null : [
            'contentType' => (string) $result['contentType'],
            'data' => $content,
        ];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed>|null */
    private static function requestProxy(string $url, string $secret, array $payload): ?array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Symo-Blob-Secret: ' . $secret,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!is_string($response) || $status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }
}

