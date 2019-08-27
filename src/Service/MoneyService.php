<?php

namespace App\Service;

/**
 * Class MoneyService
 * @package App\Service
 */
class MoneyService
{
    /**
     * MAX_MONEY_AMOUNT application wide
     */
    const MAX_MONEY_AMOUNT = 99999999;


    /**
     * ISO_CODE MONEY
     */
    const CHF = 'CHF';
    const EUR = 'EUR';

    /**
     * @param bool $value
     * @param string $type
     * @return float|mixed|string
     */
    public function valueToValidNumber($value = false, $type = 'fix')
    {
        if (!$value) {
           return 0.00;
        }

        $value = str_replace("'", '', $value);
        $value = (float) $value;

        //$value = ('fix' != $type && $value > 100) ? 100.00 : $value;
        //$value = ($value > self::MAX_MONEY_AMOUNT) ? 0.00 : $value;

        return $value;
    }
}