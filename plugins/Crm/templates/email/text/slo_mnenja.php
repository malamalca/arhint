Spoštovani,

prosim za izdajo mnenja za gradnjo objekta po dokumentaciji v priponki.

<?php
    if ($address->descript) {
        $data = json_decode($address->descript, true);
        if (!empty($data['opis'])) {
            echo 'Vloga za: ' . $data['opis'] . PHP_EOL;
        }
    }
?>



S spoštovanjem,

<?= $user->name ?>