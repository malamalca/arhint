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
                <a class="collapsible-header"<?= !empty($mainItem['url']) ? sprintf('href="%s"', $this->Url->build($mainItem['url'])) : '' ?>>
                <?= h($mainItem['title']) ?>
                </a>
                <?php
            }
            ?>
            <?php
            if (!empty($mainItem['items'])) {
                ?>
            <div class="collapsible-body sidenav-submenu-body">
                <ul>
                <?php
                foreach ($mainItem['items'] as $item) {
                    if (!empty($item['visible'])) {
                        ?>
                    <li class="<?= !empty($item['active']) && empty($item['submenu']) ? 'active' : '#!' ?>">
                            <?php
                            if (empty($item['submenu'])) {
                                ?>
                            <a href="<?= $this->Url->build($item['url']) ?>"><?= h($item['title']) ?></a>
                                <?php
                            } else {
                                ?>
                        <ul class="collapsible collapsible-accordion">
                            <li class="<?= !empty($item['active']) ? ' active' : '' ?>">
                                <a class="collapsible-header"<?= !empty($item['url']) ? sprintf('href="%s"', $this->Url->build($item['url'])) : '#!' ?>>
                                    <?= h($item['title']) ?>
                                </a>
                                <div class="collapsible-body">
                                    <ul class="sidenav-submenu">
                                    <?php
                                    foreach ($item['submenu'] as $submenuItem) {
                                        if (!empty($submenuItem['visible'])) {
                                            ?>
                                        <li class="<?= !empty($submenuItem['active']) ? ' active' : '' ?>">
                                            <a href="<?= $this->Url->build($submenuItem['url']) ?>">
                                                <?= h($submenuItem['title']) ?>
                                                <?= !empty($submenuItem['badge']) ? sprintf('<span class="new badge" data-badge-caption="%s"></span>', $submenuItem['badge']) : '' ?>
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
