<?php
header('Content-type: text/javascript; charset=iso-8859-1');
include "../admin/ashopconstants.inc.php";
?>

function switchStates(stateselector,regionbox,country) {
	var stateselectorrow = document.getElementById("stateselector");
	var regionrow = document.getElementById("regionrow");
	if (country == 'US' || country == 'United States')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($americanstates)) foreach ($americanstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'CA' || country == 'Canada')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($canprovinces)) foreach ($canprovinces as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'AU' || country == 'Australia')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($australianstates)) foreach ($australianstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'AT' || country == 'Austria')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($austriastates)) foreach ($austriastates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'BE' || country == 'Belgium')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($belgiumstates)) foreach ($belgiumstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'DE' || country == 'Germany')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($germanystates)) foreach ($germanystates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'ES' || country == 'Spain')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($spainstates)) foreach ($spainstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'FR' || country == 'France')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($francestates)) foreach ($francestates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'GB' || country == 'United Kingdom')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($ukstates)) foreach ($ukstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'IT' || country == 'Italy')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($italystates)) foreach ($italystates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'LU' || country == 'Luxembourg')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($luxembourgstates)) foreach ($luxembourgstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'NL' || country == 'Netherlands')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($netherlandsstates)) foreach ($netherlandsstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else
	{
		regionbox.disabled=false;
		regionrow.style.display='';
		stateselector.options.length=0;
		stateselectorrow.style.display='none';
	}
}

function switchStates2(stateselector,regionbox,country) {
	var stateselectorrow = document.getElementById("stateselector2");
	var regionrow = document.getElementById("regionrow2");
	if (country == 'US' || country == 'United States')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($americanstates)) foreach ($americanstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'CA' || country == 'Canada')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($canprovinces)) foreach ($canprovinces as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'AU' || country == 'Australia')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($australianstates)) foreach ($australianstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'AT' || country == 'Austria')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($austriastates)) foreach ($austriastates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'BE' || country == 'Belgium')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($belgiumstates)) foreach ($belgiumstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'DE' || country == 'Germany')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($germanystates)) foreach ($germanystates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'ES' || country == 'Spain')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($spainstates)) foreach ($spainstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'FR' || country == 'France')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($francestates)) foreach ($francestates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'GB' || country == 'United Kingdom')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($ukstates)) foreach ($ukstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'IT' || country == 'Italy')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($italystates)) foreach ($italystates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'LU' || country == 'Luxembourg')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($luxembourgstates)) foreach ($luxembourgstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else if (country == 'NL' || country == 'Netherlands')
	{
		stateselectorrow.style.display='';
		stateselector.disabled=false;
		regionbox.value='';
		regionrow.style.display='none';
		stateselector.options.length=0;
		stateselector.options[0]=new Option("choose...", "none", true, false);
		<?php
			if (is_array($netherlandsstates)) foreach ($netherlandsstates as $longstate=>$shortstate) {
				echo "stateselector.options[stateselector.options.length]=new Option(\"$longstate\", \"$shortstate\", true, false);
				";
			}
		?>
	}
	else
	{
		regionbox.disabled=false;
		regionrow.style.display='';
		stateselector.options.length=0;
		stateselectorrow.style.display='none';
	}
}