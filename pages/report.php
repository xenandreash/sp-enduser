<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!isset($reportdata)) die("Unable to report mail");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Redirecting...</title>
	</head>
	<body>
		<form action="https://report.halon.se/<?php echo $reportdata['type'] ?>/" id="reportform" method="post">
			<?php if (isset($reportdata['refid'])) { ?>
				<input type="hidden" name="refid" value="<?php echo htmlspecialchars($reportdata['refid']); ?>">
			<?php } ?>
			<?php 
				if (isset($reportdata['file'])) {
			?>
				<textarea name="email" style="display:none"><?php
				$read = 10000;
				$offset = 0;
				try {
					while (true) {
						$result = $client->fileRead(array('file' => $reportdata['file'], 'offset' => $offset, 'size' => $read));
						echo $result->data;
						flush();
						if ($result->size < $read)
							break;
						$offset = $result->offset;
					}
				} catch (SoapFault $f) { }
				?></textarea>
			<?php
				}
			?>
			<script>
				window.onload = function() {
					document.getElementById("reportform").submit();
				}
			</script>
		</form>
	</body>
</html>