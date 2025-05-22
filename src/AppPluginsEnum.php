<?php
declare(strict_types=1);

namespace App;

enum AppPluginsEnum
{
    case Calendar;
    case Crm;
    case Documents;
    case Expenses;
    case Projects;
    case Tasks;

    /**
     * Vrne index elementa znotraj enuma
     *
     * @return int
     */
    public function getOrdinal(): int
    {
        $value = array_filter($this->cases(), fn($case) => $this == $case);

        if (!empty($value)) {
            return array_keys($value)[0];
        } else {
            return -1;
        }
    }
}
