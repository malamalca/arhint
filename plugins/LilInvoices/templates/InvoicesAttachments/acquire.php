<?php
    use Cake\Routing\Router;
?>
<div>
    <button id="ButtonAcquire" onclick="LilInvoicesAcquire();"><?php echo __d('lil_invoices', 'Acquire'); ?></button>
    <button id="ButtonSource" onclick="LilInvoicesSelectSource();"><?php echo __d('lil_invoices', 'Select Source'); ?></button>
</div>

<applet id="LilScanApplet" code="LilScanApplet" archive="<?php echo Router::url('/lil/LilScan.jar', true); ?>" width="1" height="1">
    <param name="separate_jvm" value="true">
    <param name="uploadUrl" value="<?php echo Router::url(['action' => 'edit', $invoice_id], true); ?>">
    <param name="field" value="filename">
    <param name="codebase_lookup" value="false">
</applet>
<script type="text/javascript">
    function LilInvoicesAcquire()
    {
        if (typeof LilScanApplet != 'undefined' && LilScanApplet.isTwainEnabled()) {
            LilScanApplet.acquire();
            window.location.reload();
        }
    }

    function LilInvoicesSelectSource()
    {
        if (typeof LilScanApplet != 'undefined' && LilScanApplet.isTwainEnabled()) LilScanApplet.selectDefaultSource();
    }
</script>
