<?php

namespace App\Service\Interface;

interface CurrencyDataUpdaterInterface
{
    public function updateData(\DateTime $start, \DateTime $end, string $fsym, string $tsym): void;
}
