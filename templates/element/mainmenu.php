<?php
    $badParamKeys = ['confirm', 'escape'];

    // process submenus
if (!empty($main_menu)) {
    foreach ($main_menu as $itemKey => $item) {
        if (isset($item['submenu'])) {
            printf('<ul id="%s" class="dropdown-content">', 'dropdown_' . $itemKey . '_' . $prefix);
            foreach ($item['submenu'] as $subItem) {
                $params = isset($subItem['params']) ? array_diff_key($subItem['params'], array_flip($badParamKeys)) : [];
                if (isset($subItem['params']['confirm'])) {
                    $params['onclick'] = sprintf('return confirm("%s");', $subItem['params']['confirm']);
                }
                echo '<li>';
                echo $this->Html->link($subItem['title'], $subItem['url'], $params);
                echo '</li>';
            }
            print ('</ul>');
        }
    }
}
?>

<ul>
<?php
if (!empty($main_menu)) {
    foreach ($main_menu as $itemKey => $item) {
        if (!empty($item) && $item['visible']) {
            ?>
        <li>
            <?php
            // remove bad keys
            $params = isset($item['params']) ? array_diff_key($item['params'], array_flip($badParamKeys)) : [];
            if (isset($item['params']['confirm'])) {
                $params['onclick'] = sprintf('return confirm("%s");', $item['params']['confirm']);
            }
            if (isset($item['submenu'])) {
                $params['class'] = 'dropdown-trigger';
                $params['data-target'] = 'dropdown_' . $itemKey . '_' . $prefix;
                $params['escape'] = false;
                $params['url'] = '#!';
                $item['title'] .= ' <i class="material-icons right">arrow_drop_down</i>';
            }
            echo $this->Html->link($item['title'], empty($item['url']) ? '#' : $item['url'], $params);
            ?>
        </li>
            <?php
        }
    }
}
?>
</ul>
