<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class GitHubActionsHelper
{
    private const API_BASE = 'https://api.github.com';

    /** @return array{success: bool, message: string, run?: array<string, mixed>} */
    public static function dispatch(string $action): array
    {
        if (!in_array($action, ['status', 'migrate', 'seed'], true)) {
            return ['success' => false, 'message' => 'Operação de banco inválida.'];
        }

        $config = self::configuration();
        if ($config === null) {
            return ['success' => false, 'message' => 'A integração com o GitHub Actions ainda não foi configurada.'];
        }

        $before = gmdate('Y-m-d\TH:i:s\Z');
        $response = self::request(
            'POST',
            "/repos/{$config['owner']}/{$config['repository']}/actions/workflows/{$config['workflow']}/dispatches",
            $config,
            ['ref' => $config['branch'], 'inputs' => ['action' => $action]]
        );

        if (!$response['success']) {
            return ['success' => false, 'message' => 'Não foi possível iniciar o workflow do GitHub.'];
        }

        return [
            'success' => true,
            'message' => "Operação '{$action}' enviada ao GitHub Actions.",
            'run' => [
                'action' => $action,
                'requested_at' => $before,
            ],
        ];
    }

    /** @return array{success: bool, run?: array<string, mixed>, jobs?: array<int, array<string, mixed>>, message?: string} */
    public static function latestRun(?string $requestedAt = null): array
    {
        $config = self::configuration();
        if ($config === null) {
            return ['success' => false, 'message' => 'A integração com o GitHub Actions ainda não foi configurada.'];
        }

        $response = self::request(
            'GET',
            "/repos/{$config['owner']}/{$config['repository']}/actions/workflows/{$config['workflow']}/runs?event=workflow_dispatch&per_page=10",
            $config
        );
        if (!$response['success'] || !is_array($response['data']['workflow_runs'] ?? null)) {
            return ['success' => false, 'message' => 'Não foi possível consultar as execuções do GitHub.'];
        }

        $run = null;
        foreach ($response['data']['workflow_runs'] as $candidate) {
            if ($requestedAt === null || (string) ($candidate['created_at'] ?? '') >= $requestedAt) {
                $run = $candidate;
                break;
            }
        }

        if ($run === null) {
            return ['success' => true, 'run' => null, 'jobs' => []];
        }

        $jobsResponse = self::request('GET', (string) parse_url((string) ($run['jobs_url'] ?? ''), PHP_URL_PATH), $config);
        $jobs = is_array($jobsResponse['data']['jobs'] ?? null) ? $jobsResponse['data']['jobs'] : [];

        return ['success' => true, 'run' => $run, 'jobs' => $jobs];
    }

    public static function jobLog(int $jobId): ?string
    {
        $config = self::configuration();
        if ($config === null || $jobId <= 0) {
            return null;
        }

        $curl = curl_init(self::API_BASE . "/repos/{$config['owner']}/{$config['repository']}/actions/jobs/{$jobId}/logs");
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $config['token'],
                'User-Agent: Symo-Application',
                'X-GitHub-Api-Version: 2022-11-28',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return is_string($response) && $status >= 200 && $status < 300 ? $response : null;
    }

    /** @return array{owner: string, repository: string, workflow: string, branch: string, token: string}|null */
    private static function configuration(): ?array
    {
        $token = trim((string) getenv('GITHUB_ACTIONS_TOKEN'));
        $owner = trim((string) (getenv('GITHUB_REPOSITORY_OWNER') ?: 'Shxsaimon'));
        $repository = trim((string) (getenv('GITHUB_REPOSITORY_NAME') ?: 'Symo-Finance'));
        $workflow = trim((string) (getenv('GITHUB_MIGRATIONS_WORKFLOW') ?: 'database-migrations.yml'));
        $branch = trim((string) (getenv('GITHUB_MIGRATIONS_BRANCH') ?: 'main'));

        if ($token === '' || $owner === '' || $repository === '' || $workflow === '' || $branch === '') {
            return null;
        }

        return compact('owner', 'repository', 'workflow', 'branch', 'token');
    }

    /** @param array<string, string> $config @param array<string, mixed>|null $body @return array{success: bool, data: array<string, mixed>} */
    private static function request(string $method, string $path, array $config, ?array $body = null): array
    {
        if (!str_starts_with($path, '/')) {
            return ['success' => false, 'data' => []];
        }

        $curl = curl_init(self::API_BASE . $path);
        $headers = [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $config['token'],
            'User-Agent: Symo-Application',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        return [
            'success' => $status >= 200 && $status < 300,
            'data' => is_array($data) ? $data : [],
        ];
    }
}
