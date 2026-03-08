Spoštovani,

prosim za izdajo projektnih pogojev za gradnjo objekta po dokumentaciji v priponki.<br />
<?php
    if ($address->descript) {
        $data = json_decode($address->descript, true);
        if (!empty($data['opis'])) {
            echo 'Vloga za: <b>' . $data['opis'] . '</b><br />';
        }
    }
?>
<br />
S spoštovanjem,<br />
<br />
<?= $user->name ?><br />