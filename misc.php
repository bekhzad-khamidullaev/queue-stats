<?php

function return_timestamp($date_string)
{
  list ($year,$month,$day,$hour,$min,$sec) = preg_split("/-|:| /",$date_string,6);
  $u_timestamp = mktime($hour,$min,$sec,$month,$day,$year);
  return $u_timestamp;
}

function swf_bar($values,$width,$height,$divid,$stack) {

	if($stack==1) {
		$chart = "barstack.swf";
	} else {
		$chart = "bar.swf";
	}
?>
<div id="<?php echo $divid?>">
<?php echo $values?>
</div>

<script type="text/javascript">
   var fo = new FlashObject("<?php echo $chart?>", "barchart", "<?php echo $width?>", "<?php echo $height?>", "7", "#336699");
   fo.addParam("wmode", "transparent");
//   fo.addParam("salign", "t");
	<?php
		$variables = split("&",$values);
		foreach ($variables as $deauna) {
			echo "//$deauna\n";
			$pedazos = split("=",$deauna);
			echo "fo.addVariable('".$pedazos[0]."','".$pedazos[1]."');\n";
		}
	?>
   fo.write("<?php echo $divid?>");
</script>

<?php
}

function tooltip($texto,$width) {
 echo " onmouseover=\"this.T_WIDTH=$width;this.T_PADDING=5;this.T_STICKY = false; return escape('$texto')\" ";
}


function print_exports($header_pdf,$data_pdf,$width_pdf,$title_pdf,$cover_pdf,$header_csv) {
		global $lang;
		global $language;
		$headcsv_serial = serialize($header_csv);
		$head_serial = serialize($header_pdf);
		$data_serial = serialize($data_pdf);
		$width_serial = serialize($width_pdf);
		$title_serial = serialize($title_pdf);
		$cover_serial = serialize($cover_pdf);
		$head_serial = rawurlencode($head_serial);
		$data_serial = rawurlencode($data_serial);
		$width_serial = rawurlencode($width_serial);
		$title_serial = rawurlencode($title_serial);
		$cover_serial = rawurlencode($cover_serial);
		echo "<BR><form method=post action='export.php'>\n";
		echo $lang["$language"]['export'];
		echo "<input type='hidden' name='headcsv' value='".$headcsv_serial."' />\n";
		echo "<input type='hidden' name='head' value='".$head_serial."' />\n";
		echo "<input type='hidden' name='rawdata' value='".$data_serial."' />\n";
		echo "<input type='hidden' name='width' value='".$width_serial."' />\n";
		echo "<input type='hidden' name='title' value='".$title_serial."' />\n";
		echo "<input type='hidden' name='cover' value='".$cover_serial."' />\n";
		echo "<input type=image name='pdf' src='images/pdf.gif' ";
		tooltip($lang["$language"]['pdfhelp'],200);
		echo ">\n";
		echo "<input type=image name='csv' src='images/excel.png' "; 
		tooltip($lang["$language"]['csvhelp'],200);
		echo ">\n";
		echo "</form>";
}

function seconds2minutes($segundos) {
    $minutos = intval($segundos / 60);
    $segundos = $segundos % 60;
    if(strlen($segundos)==1) {
		$segundos = "0".$segundos;
	}
    return "$minutos:$segundos";
}
?>
