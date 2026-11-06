<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

/**
 * Folds leave card rows into balances, following the legacy
 * `Leave::process_leave_card()` accumulation:
 *
 *   balance += v_balance; balance -= v_abs; balance += v_earned
 *
 * including the sick-leave-to-vacation conversion the legacy applied when sick
 * leave ran out, and the terminal-leave reset (leave type 21).
 */
final class LeaveBalanceCalculator
{
    public const TERMINAL_LEAVE_TYPE = 21;

    /**
     * @param list<array<string, mixed>> $cards enabled leave_card rows, in the order the legacy queried them (date, period DESC, id)
     *
     * @return array{vacation: float, sick: float}
     */
    public function fold(array $cards): array
    {
        $vacation = 0.0;
        $sick = 0.0;

        foreach ($cards as $card) {
            $earnedV = $this->number($card['v_earned'] ?? 0);
            $earnedS = $this->number($card['s_earned'] ?? 0);
            $absV = $this->number($card['v_abs'] ?? 0);
            $absS = $this->number($card['s_abs'] ?? 0);
            $balanceV = $this->number($card['v_balance'] ?? 0);
            $balanceS = $this->number($card['s_balance'] ?? 0);
            $particulars = (string) ($card['particulars'] ?? '');

            $vacation += $balanceV;
            $sick += $balanceS;

            $vacation -= $absV;
            $sick -= $absS;

            $vacation += $earnedV;
            $sick += $earnedS;

            $isForwarded = substr($particulars, 0, 3) === 'Bal';

            if ($sick < 0 && !$isForwarded && $particulars !== '' && $absS != 0.0) {
                $overflow = abs($sick);

                if ($overflow > $absS) {
                    $sick = -($overflow - $absS);

                    if ($vacation > $absS) {
                        $vacation -= $absS;
                    }
                } else {
                    $sick = 0.0;
                    $vacation -= $overflow;
                }
            }

            if ($vacation < 0 && $absV != 0.0) {
                $overflow = abs($vacation);

                if (!($overflow > $absV)) {
                    $vacation = 0.0;
                }
            }

            if ((int) ($card['leave_type_id'] ?? 0) === self::TERMINAL_LEAVE_TYPE) {
                $vacation = 0.0;
                $sick = 0.0;
            }
        }

        return [
            'vacation' => round($vacation, 4),
            'sick' => round($sick, 4),
        ];
    }

    private function number(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
