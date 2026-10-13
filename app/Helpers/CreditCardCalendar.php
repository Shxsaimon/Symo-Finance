<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Regras de calendário para ciclos de faturas de cartão de crédito.
 */
final class CreditCardCalendar
{
    /**
     * @return array{mes: int, ano: int}
     */
    public static function invoiceReferenceForPurchase(string $purchaseDate, int $closingDay): array
    {
        $purchase = new DateTimeImmutable($purchaseDate);
        $closingDate = self::dateForMonth(
            (int) $purchase->format('Y'),
            (int) $purchase->format('n'),
            $closingDay
        );

        if ($purchase < $closingDate) {
            return ['mes' => (int) $purchase->format('n'), 'ano' => (int) $purchase->format('Y')];
        }

        $nextMonth = $purchase->modify('first day of next month');
        return ['mes' => (int) $nextMonth->format('n'), 'ano' => (int) $nextMonth->format('Y')];
    }

    /**
     * Mantém o dia original das parcelas e limita a data ao último dia do mês de destino.
     */
    public static function installmentDate(string $firstDate, int $monthOffset): string
    {
        if ($monthOffset < 0) {
            throw new InvalidArgumentException('O deslocamento da parcela não pode ser negativo.');
        }

        $first = new DateTimeImmutable($firstDate);
        $targetMonth = $first->modify('first day of this month')->modify("+{$monthOffset} months");
        $day = min((int) $first->format('j'), (int) $targetMonth->format('t'));

        return $targetMonth->setDate(
            (int) $targetMonth->format('Y'),
            (int) $targetMonth->format('n'),
            $day
        )->format('Y-m-d');
    }

    /**
     * @return array{data_fechamento: string, data_vencimento: string}
     */
    public static function invoiceDates(
        int $closingDay,
        int $dueDay,
        int $month,
        int $year
    ): array {
        if ($month < 1 || $month > 12 || $year < 1) {
            throw new InvalidArgumentException('Mês ou ano de referência inválido.');
        }

        $closingDate = self::dateForMonth($year, $month, $closingDay);
        $dueDate = self::dateForMonth($year, $month, $dueDay);

        if ($dueDay <= $closingDay) {
            $nextMonth = $closingDate->modify('first day of next month');
            $dueDate = self::dateForMonth(
                (int) $nextMonth->format('Y'),
                (int) $nextMonth->format('n'),
                $dueDay
            );
        }

        while (self::isNonBusinessDay($dueDate)) {
            $dueDate = $dueDate->modify('+1 day');
        }

        return [
            'data_fechamento' => $closingDate->format('Y-m-d'),
            'data_vencimento' => $dueDate->format('Y-m-d'),
        ];
    }

    private static function dateForMonth(int $year, int $month, int $day): DateTimeImmutable
    {
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            throw new InvalidArgumentException('Dia ou mês de calendário inválido.');
        }

        $firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $actualDay = min($day, (int) $firstDay->format('t'));

        return $firstDay->setDate($year, $month, $actualDay);
    }

    private static function isNonBusinessDay(DateTimeImmutable $date): bool
    {
        $weekday = (int) $date->format('N');
        if ($weekday >= 6) {
            return true;
        }

        $year = (int) $date->format('Y');
        $fixedHolidays = [
            '01-01',
            '04-21',
            '05-01',
            '09-07',
            '10-12',
            '11-02',
            '11-15',
            '12-25',
            '12-31',
        ];
        if ($year >= 2024) {
            $fixedHolidays[] = '11-20';
        }

        if (in_array($date->format('m-d'), $fixedHolidays, true)) {
            return true;
        }

        $easter = self::easterSunday($year);
        $movableBankHolidays = [
            $easter->modify('-48 days')->format('Y-m-d'),
            $easter->modify('-47 days')->format('Y-m-d'),
            $easter->modify('-2 days')->format('Y-m-d'),
            $easter->modify('+60 days')->format('Y-m-d'),
        ];

        return in_array($date->format('Y-m-d'), $movableBankHolidays, true);
    }

    private static function easterSunday(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
}
