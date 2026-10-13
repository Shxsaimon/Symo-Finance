<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Formatação e Sanitização
 *
 * Fornece métodos para formatação monetária padrão brasileira (R$),
 * datas e horários, parsing de decimais e escape seguro contra XSS.
 */
class FormatHelper
{
    /**
     * Formata um valor numérico para moeda brasileira (R$).
     *
     * @param float|int|string|null $value Valor a ser formatado
     * @param string $symbol Símbolo monetário (padrão 'R$')
     * @param int $decimals Casas decimais (padrão 2)
     * @return string
     */
    public static function currency(float|int|string|null $value, string $symbol = 'R$', int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            $num = 0.0;
        } elseif (is_string($value)) {
            $num = self::parseDecimal($value);
        } else {
            $num = (float) $value;
        }

        $formatted = number_format($num, $decimals, ',', '.');
        return !empty($symbol) ? "{$symbol} {$formatted}" : $formatted;
    }

    /**
     * Formata uma data ISO (YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS) para formato brasileiro (d/m/Y).
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function date(?string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }

        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (Throwable) {
            return (string) $date;
        }
    }

    /**
     * Formata data e hora para o padrão brasileiro (d/m/Y H:i).
     *
     * @param string|null $dateTime
     * @param string $format
     * @return string
     */
    public static function dateTime(?string $dateTime, string $format = 'd/m/Y H:i'): string
    {
        return self::date($dateTime, $format);
    }

    /**
     * Converte representações textuais brasileiras ("1.250,50" ou "1250,50") ou internacionais ("1250.50") em float puro.
     *
     * @param string|float|int|null $value
     * @return float
     */
    public static function parseDecimal(string|float|int|null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        $clean = trim((string) $value);
        
        // Remove símbolos de moeda e espaços (ex: "R$ 1.500,00" -> "1.500,00")
        $clean = preg_replace('/[^\d,\.\-]/', '', $clean) ?? '';

        // Se contiver vírgula e ponto (ex: "1.250,50")
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            // Se contiver apenas vírgula (ex: "1250,50")
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }

    /**
     * Converte representações monetárias brasileiras (ex: "R$ 1.500,50") ou numéricas em float puro.
     * Alias semântico para parseDecimal().
     *
     * @param string|float|int|null $value
     * @return float
     */
    public static function parseCurrency(string|float|int|null $value): float
    {
        return self::parseDecimal($value);
    }

    /**
     * Formata um percentual.
     *
     * @param float|int|string|null $value
     * @param int $decimals
     * @return string
     */
    public static function percentage(float|int|string|null $value, int $decimals = 2): string
    {
        $num = is_string($value) ? self::parseDecimal($value) : (float) ($value ?? 0);
        return number_format($num, $decimals, ',', '.') . '%';
    }

    /**
     * Remove dos lançamentos parcelados o sufixo legado que duplica o badge da parcela.
     * Aceita tanto o array da transação quanto uma string de descrição diretamente.
     *
     * @param array<string, mixed>|string $transaction
     */
    public static function transactionDescription(array|string $transaction): string
    {
        if (is_string($transaction)) {
            return (string) preg_replace('/\s+\[\d+\/\d+\]$/', '', $transaction);
        }

        $description = (string) ($transaction['descricao'] ?? '');
        $installment = (int) ($transaction['numero_parcela'] ?? 0);
        $installmentCount = (int) ($transaction['total_parcelas'] ?? 0);

        if ($installmentCount > 1 && $installment > 0) {
            $suffix = sprintf(' [%d/%d]', $installment, $installmentCount);
            if (str_ends_with($description, $suffix)) {
                return substr($description, 0, -strlen($suffix));
            }
        }

        return $description;
    }

    /**
     * Escapa dados para saída segura em HTML contra XSS.
     *
     * @param mixed $data
     * @return string
     */
    public static function escape(mixed $data): string
    {
        if ($data === null) {
            return '';
        }
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }
}
