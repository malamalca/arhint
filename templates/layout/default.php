<?php
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Routing\Router;
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= strip_tags($this->fetch('title')) ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css('main.css') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <?= $this->Html->script('/lil/js/jquery.min.js') ?>
    <?= $this->Html->script('/js/vendor/Materialize/bin/materialize.js') ?>
    <?= $this->Html->script('/lil/js/lil_float.js') ?>
    <?= $this->Html->script('/lil/js/lil_date.js') ?>
    <?= $this->Html->script('monthpicker.js') ?>
    <?= $this->Html->script('modalPopup.js') ?>
    <?= $this->Html->script('autocompleteAjax.js') ?>

    <?= $this->fetch('script') ?>
</head>
<body>
    <header>
        <div class="navbar-fixed">
            <nav role="navigation">
                <div class="nav-wrapper container">
                    <a id="logo-container" href="#" class="brand-logo">
                        <div class="logo center-block"><?= Configure::read('Lil.appTitle') ?></div>
                    </a>
                    <div class="hide-on-med-and-down">
                        <?= $this->element('mainmenu', ['prefix' => 'top']) ?>
                    </div>
                    <!-- Sidebar button for mobile devices -->
                    <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
                </div>
            </nav>
            </div>
            <ul id="slide-out" class="sidenav sidenav-fixed">
                <!-- Sidebar -->
                <li class="sidenav-user">
                    <?= $this->Html->image(
                        Router::url(
                            ['plugin' => false, 'controller' => 'Users', 'action' => 'avatar', $this->getCurrentUser()->get('id')],
                            true
                        ),
                        ['class' => 'sidenav-avatar circle']
                    ) ?>
                    <?php
                    if ($this->getCurrentUser() && $this->getCurrentUser()->get('id')) {
                        $isUserProperties = $this->getRequest()->getParam('controller') == 'Users' &&
                            $this->getRequest()->getParam('action') == 'properties';
                        ?>
                    <div class="sidenav-user-title"><?= h($this->getCurrentUser()->get('name')) ?></div>
                    <ul class="collection" id="user-settings" style="display: <?= $isUserProperties ? 'default' : 'none' ?>">
                        <li class="<?= $isUserProperties ? 'active' : '' ?>">
                            <?= $this->Html->link(__('Settings'), ['plugin' => false, 'controller' => 'Users', 'action' => 'properties']) ?>
                        </li>
                        <li><?= $this->Html->link(__('Logout'), ['plugin' => false, 'controller' => 'Users', 'action' => 'logout']) ?></li>
                    </ul>
                        <?php
                    }
                    ?>
                </li>
                <!--<li class="sidenav-header"><?= $this->Html->link(Configure::read('Lil.appTitle'), '/') ?></a></li>-->
                <li class="sidenav-menu no-padding hide-on-large-only">
                    <?= $this->element('mainmenu', ['prefix' => 'side']) ?>
                </li>
                <li class="sidenav-menu no-padding">
                    <?= $this->element('sidenav') ?>
                </li>
            </ul>

    </header>

    <!-- Contents -->
    <main>
        <div class="container">
            <?= $this->Flash->render() ?>

            <?php
            if ($title = $this->fetch('title')) {
                if ($title != '&nbsp;') {
                    printf('<h2>%s</h2>', $title);
                }
            }
            ?>

            <?= $this->fetch('content') ?>
            <br /><br />
        </div>
    </main>

    <footer>
    </footer>
    <script type="text/javascript">
        <?php
            //lilFloat settings should be made before $(document).ready();
            $formatter = $this->Number->formatter();
        ?>

        lilFloatSetup.decimalSeparator = "<?= $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL); ?>";
        lilFloatSetup.thousandsSeparator = "<?= $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL); ?>";

        $(document).ready(function(){
            M.AutoInit();

            $(".sidenav-avatar, .sidenav-user-title").on("click", function(e) {
                $("#user-settings").toggle();
            });

            <?= $this->Lil->jsReadyOut(); ?>
        });
    </script>
</body>
</html>
