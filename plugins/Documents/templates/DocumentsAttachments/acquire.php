<?php
    use Cake\Routing\Router;
?>
<div>
    <button id="ButtonAcquire" onclick="DocumentsAcquire();"><?php echo __d('documents', 'Acquire'); ?></button>
    <button id="ButtonSource" onclick="DocumentsSelectSource();"><?php echo __d('documents', 'Select Source'); ?></button>
</div>

<applet id="LilScanApplet" code="LilScanApplet" archive="<?php echo Router::url('/lil/LilScan.jar', true); ?>" width="1" height="1">
    <param name="separate_jvm" value="true">
    <param name="uploadUrl" value="<?php echo Router::url(['action' => 'edit', $document_id], true); ?>">
    <param name="field" value="filename">
    <param name="codebase_lookup" value="false">
</applet>
<script type="text/javascript">
    function DocumentsAcquire()
    {
        if (typeof LilScanApplet != 'undefined' && LilScanApplet.isTwainEnabled()) {
            LilScanApplet.acquire();
            window.location.reload();
        }
    }

    function DocumentsSelectSource()
    {
        if (typeof LilScanApplet != 'undefined' && LilScanApplet.isTwainEnabled()) LilScanApplet.selectDefaultSource();
    }
</script>
