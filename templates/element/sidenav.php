<ul class="collapsible collapsible-accordion">
<?php
if (!empty($sidebar)) {
    foreach ($sidebar as $mainItem) {
        if (!empty($mainItem['visible'])) {
            ?>
        <li class="bold<?= !empty($mainItem['active']) ? ' active' : '' ?>">
            <?php
            if (!empty($mainItem['title'])) {
                ?>
                <a class="sidenav-header"<?= !empty($mainItem['url']) ? sprintf('href="%s"', $this->Url->build($mainItem['url'])) : '' ?>>
                <?= h($mainItem['title']) ?>
                </a>
                <?php
            }
            ?>
            <?php
            if (!empty($mainItem['items'])) {
                ?>
            <div class="collapsible-body">
                <ul>
                <?php
                foreach ($mainItem['items'] as $item) {
                    if (!empty($item['visible'])) {
                        ?>
                    <li class="<?= !empty($item['active']) && empty($item['submenu']) ? 'active' : '' ?>">
                            <?php
                            if (empty($item['submenu'])) {
                                ?>
                            <a href="<?= $this->Url->build($item['url']) ?>"><?= h($item['title']) ?></a>
                                <?php
                            } else {
                                ?>
                        <ul class="collapsible collapsible-accordion">
                            <li class="waves-effect<?= !empty($item['active']) ? ' active' : '' ?>">
                                <a class="collapsible-header"<?= !empty($item['url']) ? sprintf('href="%s"', $this->Url->build($item['url'])) : '' ?>>
                                    <?= h($item['title']) ?>
                                    <i class="material-icons chevron">arrow_drop_down</i>
                                </a>
                                <div class="collapsible-body">
                                    <ul class="sidenav-submenu">
                                    <?php
                                    foreach ($item['submenu'] as $submenuItem) {
                                        if (!empty($submenuItem['visible'])) {
                                            ?>
                                        <li class="waves-effect<?= !empty($submenuItem['active']) ? ' active' : '' ?>">
                                            <a href="<?= $this->Url->build($submenuItem['url']) ?>">
                                            <?= h($submenuItem['title']) ?>
                                                <i class="material-icons chevron"></i>
                                            </a>
                                        </li>
                                            <?php
                                        }
                                    }
                                    ?>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                                <?php
                            }
                            ?>
                    </li>
                            <?php
                    }
                }
                ?>
                </ul>
            </div>
                <?php
            }
            ?>
        </li>
            <?php
        }
    }
}
?>
</ul>
