<?php
use Cake\Core\Configure;
use Cake\Routing\Router;
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= strip_tags($pageTitle ?? $this->fetch('title')) ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Router::url('/img/') ?>apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Router::url('/img/') ?>favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= Router::url('/img/') ?>favicon-16x16.png">
    <link rel="manifest" href="<?= Router::url('/') ?>site.webmanifest">

    <?= $this->Html->css('main.css') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <?= $this->Html->script('/js/jquery/jquery-3.6.0.min.js') ?>
    <?= $this->Html->script('/js/vendor/Materialize/materialize.js') ?>
    <?= $this->Html->script('/lil/js/lil_float.js') ?>
    <?= $this->Html->script('modalPopup.js') ?>

    <?= $this->fetch('script') ?>
</head>
<body>
    <header>
        <nav class="nav navbar" role="navigation">
            <div class="nav-wrapper">
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
        
            <ul id="slide-out" class="sidenav sidenav-fixed">
                <!-- Sidebar -->
                <li class="sidenav-user">
                    <a href="<?= Router::url('/', true) ?>" class="sidenav-avatar">
                    <?= $this->Html->image(
                        Router::url(
                            array_filter([
                                'plugin' => false,
                                'controller' => 'Users',
                                'action' => 'avatar',
                                $this->hasCurrentUser() ? $this->getCurrentUser()->get('id') : null,
                                '_ext' => 'png',
                            ], fn($var) => $var !== null),
                            true,
                        ),
                        ['class' => 'circle'],
                    ) ?>
                    </a>
                    <?php
                        if ($this->hasCurrentUser() && $this->getCurrentUser()->get('id')) {
                            $isUserProperties = $this->getRequest()->getParam('controller') == 'Users' &&
                                $this->getRequest()->getParam('action') == 'properties';
                            $isUtils = $this->getRequest()->getParam('controller') == 'Utils';
                    ?>
                    <div class="sidenav-user-title"><?= h($this->getCurrentUser()->get('name')) ?></div>
                    <ul class="collection" id="user-settings" style="display: <?= $isUserProperties || $isUtils ? 'default' : 'none' ?>">
                        <li class="<?= $isUserProperties ? 'active' : '' ?>">
                            <?= $this->Html->link(__('Settings'), ['plugin' => false, 'controller' => 'Users', 'action' => 'properties']) ?>
                        </li>
                        <li class="<?= $isUtils ? 'active' : '' ?>">
                            <?= $this->Html->link(__('Utils'), ['plugin' => false, 'controller' => 'Utils', 'action' => 'pdfSign']) ?>
                        </li>
                        <li><?= $this->Html->link(__('Logout'), ['plugin' => false, 'controller' => 'Users', 'action' => 'logout']) ?></li>
                    </ul>
                        <?php
                    }
                    ?>
                </li>
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
            $title = $this->fetch('title');
            if (!empty($title)) {
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
        
        $(document).ready(function() {
            <?= $this->Lil->jsReadyOut(); ?>

            M.AutoInit(document.body,{
                Collapsible: {
                    inDuration: 0,
                    outDuration: 0,
                    onOpenEnd: function(el) {
                        var parentBody = $(el).closest(".collapsible-body");
                        $(parentBody).get(0).style.maxHeight = parentBody.prop("scrollHeight") + "px";
                    }
                },
                Dropdown: {
                    constrainWidth: false,
                    coverTrigger: false
                }
            });

            // Materializeweb wont expand the parent collapsible bodies when nested collapsibles have active items
            $(".collapsible-body").each(function() {
                var parentBody = $(this).closest(".collapsible-body");
                if ($(this).find(".active").length > 0) {
                    $(parentBody).get(0).style.maxHeight = parentBody.prop("scrollHeight") + "px";
                }
            });

            // Prevent collapsible header click when having a link so open collapsible does not happen
            $(".sidenav-menu > ul.collapsible > li > a").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.location.href = $(this).attr("href");
            });

            $(".sidenav-user-title").on("click", function(e) {
                $("#user-settings").toggle();
            });
        });
    </script>
</body>
</html>
