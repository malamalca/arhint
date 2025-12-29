<?php 
    $this->assign('title', '&nbsp;');

    $this->set('main_menu', [
        'back' => [
            'visible' => true,
            'title' => '<< ' . __('Back'),
            'url' => 'javascript:window.history.back();'
        ],
        'download' => [
            'visible' => true,
            'title' => __('Download'),
            'url' => ['action' => 'pdf', $pdfFileName . '.pdf']
        ]
    ]);
?>
<iframe src="<?= $this->Url->build(['action' => 'pdf', $pdfFileName]) ?>"></iframe>

<script type="text/javascript">
    $(document).ready(function() {
        $("iframe").parent().css("margin", "0");


        function resizeIframe()
        {
            let iFrameHeight = $(window).height() - $("nav.navbar").height() - 32;
            let iFrameWidth = $(window).width() - $(".sidenav-fixed").width() - 4;

            $("iframe")
                .height(iFrameHeight)
                .width(iFrameWidth);

        }
        
        
        resizeIframe();


        $(window).on("resize", function() {
           resizeIframe();
        });
    });
</script>
