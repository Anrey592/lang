<?php
function pluralizeYears($years)
{
    $years = abs($years);

    $cases = array(2, 0, 1, 1, 1, 2);
    $titles = array("год", "года", "лет");

    return $years . " " . $titles[($years % 100 > 4 && $years % 100 < 20) ? 2 : $cases[min($years % 10, 5)]];
}