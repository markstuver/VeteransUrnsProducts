
<html>

<head>
</head>

<body>

<?php 

    $engravable = "n";
	$engravingType = "###";
	$hasMatchingKeepsake = "###";
	$matchingKeepsake = "###";

?>



<p>
<span style="padding-left: 10px; font-size: large; font-weight:bolder;">Description:</span><span style="font-size: large; font-weight: normal;"> Normal/Public Description</span>
  <br><br>
  <span style="padding-left: 10px; font-size: large; font-weight:bolder;">Size (h x w x d):</span><span style="font-size: large; font-weight: normal;"> ### height ### width ### depth</span>
  <br><br>
  <span style="padding-left: 10px; font-size: large; font-weight:bolder;">Capacity (cubic inches):</span><span style="font-size: large; font-weight: normal;"> ### cubic inches</span>
  <br><br>
  
<span style="padding-left: 10px; font-size: large; font-weight:bolder;">Matching Token/Keepsake:</span><span style="font-size: large; font-weight: normal;"> 35-19106 Old Glory II</span><br> <br>

  
  <span style="padding-left: 10px; font-size: large; font-weight:bolder;">Capacity (cubic inches):</span><span style="font-size: large; font-weight: normal;"> ### cubic inches</span>
  <br><br>

<!--CONDITIONAL PHP CODE--> 
 <?php if($engravable == "y"): ?>
  <span style="padding-left: 10px; font-size: large; font-weight:bolder;">This product can be personalized by directly engraving using our Laser Engraving.</span><br><br>
<?php elseif ($engravable == "n"): ?>
  <span style="padding-left: 10px; font-size: large; font-weight:bolder;">This product can not be directly engraved. Please ask us about our alternative personalization options.</span><br><br>
<?php else: ?>

 <span style="padding-left: 10px; font-size: large; font-weight:bolder;">Something is wrong... there is an error!</span>
<?php endif; ?>
</p>

</body>
</html>