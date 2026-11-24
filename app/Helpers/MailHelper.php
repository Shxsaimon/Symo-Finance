<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de E-mails Transacionais (MailHelper via Resend API)
 *
 * Envia e-mails transacionais (verificação de conta, redefinição de senha) utilizando
 * a API REST oficial do Resend via cURL nativo do PHP, sem dependências externas.
 */
class MailHelper
{
    private const RESEND_API_URL = 'https://api.resend.com/emails';

    /**
     * Retorna as configurações do serviço de e-mail.
     *
     * @return array{api_key: string, from_email: string}
     */
    public static function configuration(): array
    {
        $apiKey = trim((string) (getenv('RESEND_API_KEY') ?: ''));
        $fromEmail = trim((string) (getenv('RESEND_FROM_EMAIL') ?: 'Symo Finance <onboarding@resend.dev>'));

        return [
            'api_key'    => $apiKey,
            'from_email' => $fromEmail !== '' ? $fromEmail : 'Symo Finance <onboarding@resend.dev>',
        ];
    }

    /**
     * Verifica se o serviço de e-mail possui API Key configurada no ambiente.
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        $config = self::configuration();
        return $config['api_key'] !== '';
    }

    /**
     * Envia um e-mail transacional via Resend API REST.
     *
     * @param string $toEmail E-mail do destinatário
     * @param string $subject Assunto do e-mail
     * @param string $htmlBody Conteúdo em HTML
     * @param string $textBody Conteúdo em texto puro (fallback)
     * @return array{success: bool, id?: string, message: string}
     */
    public static function send(string $toEmail, string $subject, string $htmlBody, string $textBody): array
    {
        $config = self::configuration();
        if ($config['api_key'] === '') {
            $msg = 'Serviço de e-mail não configurado (RESEND_API_KEY ausente).';
            LoggerHelper::security('MAIL_NOT_CONFIGURED', ['to' => $toEmail, 'subject' => $subject]);
            return ['success' => false, 'message' => $msg];
        }

        $payload = [
            'from'    => $config['from_email'],
            'to'      => [$toEmail],
            'subject' => $subject,
            'html'    => $htmlBody,
            'text'    => $textBody,
        ];

        $ch = curl_init(self::RESEND_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $config['api_key'],
                'Content-Type: application/json',
                'User-Agent: Symo-Finance/2.4',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            $errorDetail = is_string($response) ? substr($response, 0, 500) : $curlError;
            LoggerHelper::error(
                new RuntimeException("Falha no envio de e-mail via Resend (HTTP {$httpCode}): {$errorDetail}"),
                [
                    'context'  => 'MailHelper::send',
                    'to'       => $toEmail,
                    'subject'  => $subject,
                    'httpCode' => $httpCode,
                ]
            );

            // Extrai mensagem amigável retornada pelo Resend quando disponível
            $resendMsg = 'Não foi possível enviar o e-mail no momento. Tente novamente em instantes.';
            if (is_string($response)) {
                $decoded = json_decode($response, true);
                if (is_array($decoded) && !empty($decoded['message'])) {
                    $resendMsg = (string) $decoded['message'];
                }
            }

            return ['success' => false, 'message' => $resendMsg];
        }

        $data = json_decode((string) $response, true);
        $emailId = is_array($data) && !empty($data['id']) ? (string) $data['id'] : null;

        return [
            'success' => true,
            'id'      => $emailId ?? 'ok',
            'message' => 'E-mail enviado com sucesso.',
        ];
    }

    /**
     * Envia o código de 6 dígitos para verificação de conta recém-cadastrada.
     *
     * @param string $toEmail
     * @param string $nome
     * @param string $code Código de 6 dígitos
     * @return array{success: bool, message: string}
     */
    public static function sendVerificationCode(string $toEmail, string $nome, string $code): array
    {
        $safeNome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $subject = "Seu código de verificação: {$safeCode} — Symo Finance";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Verificação de Conta</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1c1c;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f7f9fa; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 520px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e3e2e2; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
          <!-- Header com Brand -->
          <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #6f02ff 0%, #5300c4 100%); text-align: center;">
              <span style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">Symo Finance</span>
              <p style="margin: 4px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.85);">Gestão Financeira Pessoal</p>
            </td>
          </tr>
          <!-- Conteúdo -->
          <tr>
            <td style="padding: 32px;">
              <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #1a1c1c;">Confirme seu endereço de e-mail</h2>
              <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.5; color: #4a4457;">
                Olá, <strong>{$safeNome}</strong>! Obrigado por criar sua conta no Symo. Para garantir a segurança dos seus dados, informe o código de 6 dígitos abaixo na tela de verificação:
              </p>
              
              <!-- Caixa de Código de 6 dígitos -->
              <div style="background-color: #f5efff; border: 2px dashed #6f02ff; border-radius: 10px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #6f02ff; display: inline-block;">
                  {$safeCode}
                </span>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #7b7489; font-weight: 500;">Válido por 15 minutos</p>
              </div>

              <p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.5; color: #7b7489;">
                Se você não solicitou este cadastro no Symo Finance, nenhuma ação é necessária; este código expirará automaticamente.
              </p>
            </td>
          </tr>
          <!-- Rodapé -->
          <tr>
            <td style="padding: 20px 32px; background-color: #faf9fb; border-top: 1px solid #f0edf4; text-align: center;">
              <p style="margin: 0; font-size: 12px; color: #9c94a8;">
                © Symo Finance — Gestão Financeira Segura e Privada.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $text = "Olá, {$nome}!\n\n"
            . "Seu código de verificação para o Symo Finance é: {$code}\n\n"
            . "Este código expira em 15 minutos.\n"
            . "Se você não solicitou este cadastro, desconsidere esta mensagem.";

        return self::send($toEmail, $subject, $html, $text);
    }

    /**
     * Envia o link seguro para redefinição de senha esquecida.
     *
     * @param string $toEmail
     * @param string $nome
     * @param string $resetUrl URL completa para redefinir senha com token
     * @return array{success: bool, message: string}
     */
    public static function sendPasswordReset(string $toEmail, string $nome, string $resetUrl): array
    {
        $safeNome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $subject = 'Redefinição de Senha — Symo Finance';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Redefinição de Senha</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1c1c;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f7f9fa; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 520px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e3e2e2; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
          <!-- Header com Brand -->
          <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #6f02ff 0%, #5300c4 100%); text-align: center;">
              <span style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">Symo Finance</span>
              <p style="margin: 4px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.85);">Gestão Financeira Pessoal</p>
            </td>
          </tr>
          <!-- Conteúdo -->
          <tr>
            <td style="padding: 32px;">
              <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #1a1c1c;">Redefinir sua senha</h2>
              <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.5; color: #4a4457;">
                Olá, <strong>{$safeNome}</strong>! Recebemos uma solicitação para redefinir a senha de acesso à sua conta no Symo Finance.
              </p>
              
              <!-- Botão de Ação -->
              <div style="text-align: center; margin: 28px 0;">
                <a href="{$safeUrl}" style="background-color: #6f02ff; color: #ffffff; text-decoration: none; padding: 14px 28px; font-size: 15px; font-weight: 700; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(111,2,255,0.25);">
                  Criar Nova Senha
                </a>
                <p style="margin: 12px 0 0 0; font-size: 12px; color: #7b7489;">Este link é válido por 60 minutos.</p>
              </div>

              <p style="margin: 0 0 12px 0; font-size: 13px; line-height: 1.5; color: #7b7489;">
                Se o botão acima não funcionar, copie e cole o link a seguir no seu navegador:
              </p>
              <p style="margin: 0 0 20px 0; font-size: 12px; line-height: 1.4; color: #6f02ff; word-break: break-all;">
                <a href="{$safeUrl}" style="color: #6f02ff;">{$safeUrl}</a>
              </p>

              <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #7b7489;">
                Se você não fez essa solicitação, pode ignorar este e-mail com segurança. Sua senha permanecerá inalterada.
              </p>
            </td>
          </tr>
          <!-- Rodapé -->
          <tr>
            <td style="padding: 20px 32px; background-color: #faf9fb; border-top: 1px solid #f0edf4; text-align: center;">
              <p style="margin: 0; font-size: 12px; color: #9c94a8;">
                © Symo Finance — Gestão Financeira Segura e Privada.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $text = "Olá, {$nome}!\n\n"
            . "Recebemos uma solicitação para redefinir a sua senha no Symo Finance.\n\n"
            . "Para criar uma nova senha, acesse o link abaixo (válido por 60 minutos):\n"
            . "{$resetUrl}\n\n"
            . "Se você não solicitou a redefinição, nenhuma ação é necessária.";

        return self::send($toEmail, $subject, $html, $text);
    }

    /**
     * Envia um aviso por e-mail sobre um novo convite de compartilhamento de Conta ou Investimento.
     *
     * @param string $toEmail E-mail do destinatário convidado
     * @param string $toName Nome do destinatário convidado
     * @param string $ownerName Nome do proprietário que convidou
     * @param string $resourceType 'conta' ou 'investimento'
     * @param string $resourceName Nome da conta bancária ou investimento
     * @param string $permissionLabel Descrição legível da permissão concedida
     * @return array{success: bool, message: string}
     */
    public static function sendShareInvite(
        string $toEmail,
        string $toName,
        string $ownerName,
        string $resourceType,
        string $resourceName,
        string $permissionLabel
    ): array {
        $safeToName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeOwnerName = htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8');
        $safeResourceName = htmlspecialchars($resourceName, ENT_QUOTES, 'UTF-8');
        $safePermission = htmlspecialchars($permissionLabel, ENT_QUOTES, 'UTF-8');
        $tipoLabel = $resourceType === 'investimento' ? 'o investimento' : 'a conta bancária';
        $tipoTitulo = $resourceType === 'investimento' ? 'Investimento Compartilhado' : 'Conta Compartilhada';
        $appUrl = htmlspecialchars(defined('BASE_URL') ? BASE_URL . '/dashboard' : '', ENT_QUOTES, 'UTF-8');

        $subject = "Convite de {$tipoTitulo}: {$resourceName} — Symo Finance";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Convite de Compartilhamento</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1c1c;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f7f9fa; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 520px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e3e2e2; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
          <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #6f02ff 0%, #5300c4 100%); text-align: center;">
              <span style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">Symo Finance</span>
              <p style="margin: 4px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.85);">Colaboração e Gestão Financeira</p>
            </td>
          </tr>
          <tr>
            <td style="padding: 32px;">
              <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #1a1c1c;">Você recebeu um convite!</h2>
              <p style="margin: 0 0 18px 0; font-size: 15px; line-height: 1.5; color: #4a4457;">
                Olá, <strong>{$safeToName}</strong>! <strong>{$safeOwnerName}</strong> convidou você para colaborar com {$tipoLabel} <strong>"{$safeResourceName}"</strong> no Symo Finance.
              </p>
              <div style="background-color: #f5efff; border-left: 4px solid #6f02ff; border-radius: 8px; padding: 16px; margin: 20px 0;">
                <p style="margin: 0 0 6px 0; font-size: 13px; color: #7b7489; text-transform: uppercase; font-weight: 700;">Nível de Permissão</p>
                <p style="margin: 0; font-size: 15px; font-weight: 700; color: #5300c4;">{$safePermission}</p>
              </div>
              <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.5; color: #4a4457;">
                Para aceitar ou recusar este convite, acesse sua conta no Symo Finance, clique na sua foto de perfil no canto superior direito e abra a opção <strong>Notificações</strong>.
              </p>
              <div style="text-align: center; margin: 24px 0;">
                <a href="{$appUrl}" style="background-color: #6f02ff; color: #ffffff; text-decoration: none; padding: 13px 26px; font-size: 15px; font-weight: 700; border-radius: 8px; display: inline-block;">
                  Acessar o Symo Finance
                </a>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding: 20px 32px; background-color: #faf9fb; border-top: 1px solid #f0edf4; text-align: center;">
              <p style="margin: 0; font-size: 12px; color: #9c94a8;">
                © Symo Finance — Gestão Financeira Segura e Privada.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $text = "Olá, {$toName}!\n\n"
            . "{$ownerName} convidou você para compartilhar {$tipoLabel} \"{$resourceName}\" no Symo Finance.\n"
            . "Permissão concedida: {$permissionLabel}\n\n"
            . "Para aceitar ou recusar o convite, acesse o Symo Finance e abra suas Notificações no menu do perfil:\n"
            . (defined('BASE_URL') ? BASE_URL . '/dashboard' : '');

        return self::send($toEmail, $subject, $html, $text);
    }
}
