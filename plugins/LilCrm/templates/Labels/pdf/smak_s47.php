<?php
    $col_spacing = 67;
    $row_spacing = 43;
    $margin_top = 6;
    $margin_left = 10;
    $line_spacing = 5;

    $row = 1;
if (!empty($data['start_row'])) {
    $row = $data['start_row'];
}

    $col = 1;
if (!empty($data['start_col'])) {
    $col = $data['start_col'];
}

    $start_no = ($row - 1) * 3 + $col;

    $this->addPage();
$i = 0;

foreach ($addresses as $address) {
    $source = $address;
    if (!empty($address->contacts_address)) {
        $source = $address->contacts_address;
    }

    $full_position = $start_no + $i - 1;
    if ((($full_position % 21 ) == 0) && ($full_position > 0)) {
        $this->addPage();
    }

    $position = ($start_no + $i) % 21;

    $position_row = (int)floor(($position - 1) / 3);
    $position_col = $position - $position_row * 3 - 1;

    $top_x = $margin_left + $col_spacing * $position_col;
    $top_y = $margin_top + $row_spacing * $position_row;

    $this->setXY($top_x, $top_y);
    $this->multiCell(60, 0, implode(PHP_EOL, array_filter([
        $address->title,
        $source->street,
        PHP_EOL . trim($source->zip) . ' ' . $source->city,
    ])), 0, 'L');

    $i++;
}
