<?php

namespace App\Service\Interface;

interface DataSaverInterface
{
    /**
     * Saves currency data to the database.
     *
     * @param array $data The array of currency data to save.
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     */
    public function saveData(array $data, string $fsym, string $tsym);
}