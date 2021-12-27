<?php
foreach ($addresses as $address) {
    $source = $address;
    if (!empty($address->contacts_address)) {
        $source = $address->contacts_address;
    }

    $this->addPage();

    $topX = 10;

    $this->Text(7, $topX, $data['sprejemna_posta'] ?? '');
    $this->Text(62, $topX, $data['datum'] ?? '');

    $this->Text(123, $topX * 6, 'ARHIM d.o.o.');
    $this->Text(123, $topX * 6 + 9, 'Slakova ulica 36');
    $this->Text(123, $topX * 6 + 18, '8210 Trebnje');

    $this->Text(7, $topX + 5, $address->title);
    $this->Text(7, $topX + 10, $source->street);
    $this->Text(7, $topX + 15, $source->zip . ' ' . $source->city);

    $fs = $this->getFontSizePt();
    $this->SetFontSize(7);
    $this->Text(123, $topX * 6 + 4, $data['podnaslov'] ?? '');

    $this->SetFontSize($fs);
}
