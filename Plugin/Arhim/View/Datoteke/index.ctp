<div>
<h1>Skeniraj datoteko</h1>
<div>
	<select name="project" id="projects">
		<?php
			foreach ($projects as $id => $project_name) {
				printf('<option id="p%1$s" class="project-option" value="%1$s">%2$s</option>', $id, $project_name);
			}
		?>
	</select>
	<br /><br />
</div>

<div>
	<button id="ButtonAcquire" onclick="LilAcquire();"><?php echo __d('lil_invoices', 'Acquire'); ?></button>
	<button id="ButtonSource" onclick="LilSelectSource();"><?php echo __d('lil_invoices', 'Select Source'); ?></button>
</div>

<applet id="LilScanApplet" code="LilScanApplet" archive="LilScan.jar" width="1" height="1">
	<param name="separate_jvm" value="true">
	<param name="jnlp_href" value="<?php echo Router::url('/lil/lil_scan_applet.jnlp', true); ?>">
	<param name="uploadUrl" value="<?php echo Router::url(array('action' => 'attachment_add', $project_id), true); ?>">
	<param name="field" value="data[LilAttachment][filename]">
	<param name="codebase_lookup" value="false">
</applet>
<script type="text/javascript">
	function LilAcquire()
	{
		if (typeof LilScanApplet != 'undefined' && LilScanApplet.isTwainEnabled()) {
			LilScanApplet.acquire();
			window.location.reload();
		}
	}
	
	function LilSelectSource()
	{
		if (typeof LilScanApplet != 'undefined' && LilScanApplet.isTwainEnabled()) LilScanApplet.selectDefaultSource();
	}
	
	var selectProjectUrl = "<?php echo Router::url(); ?>";
	$(document).ready(function() {
		$("#projects").change(function() {
			document.location.href = selectProjectUrl + '?project=' + $(this).val();
			
		}); 
	});
</script>
</div>
<h2>Danes prenešene datoteke</h2>