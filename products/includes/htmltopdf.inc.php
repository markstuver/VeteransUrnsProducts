<?php
if (isset($ashoppath) && file_exists("$ashoppath/tcpdf/config/lang/eng.php")) {
	require_once("$ashoppath/tcpdf/config/lang/eng.php");
	require_once("$ashoppath/tcpdf/tcpdf.php");

	// create new PDF document
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	// set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor($pdfauthor);
	$pdf->SetTitle($pdftitle);
	$pdf->SetSubject($pdfsubject);
	$pdf->SetKeywords($pdfkeywords);

	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

	//set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

	//set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	
	//set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

	//set some language-dependent strings
	$pdf->setLanguageArray($l);

	// set font
	$pdf->SetFont('dejavusans', '', 14);

	// add a page
	$pdf->AddPage();

	// create some HTML content
	$pdfhtml = utf8_encode($pdfcontent);

	// output the HTML content
	$pdf->writeHTML($pdfhtml, true, false, true, false, '');

	//Close and output PDF document
	$pdf->Output($pdffilename, 'F');
}