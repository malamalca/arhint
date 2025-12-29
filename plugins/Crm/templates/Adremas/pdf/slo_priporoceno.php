<?php

foreach ($addresses as $address) {
    $source = $address;
    if (!empty($address->contacts_address)) {
        $source = $address->contacts_address;
    }

    $this->addPage();

    $this->Text(10 - 10, 32 - 8, $address->contact->title);
    $this->Text(10 - 10, 42 - 7, $source->street);
    $this->Text(10 - 10, 52 - 6, $source->zip);
    $this->Text(42 - 5, 52 - 6, $source->city);
}
