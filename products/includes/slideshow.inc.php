<script type="text/javascript">

/*** 
    Simple jQuery Slideshow Script
    Released by Jon Raasch (jonraasch.com) under FreeBSD license: free to use or modify, not responsible for anything, etc.  Please link out to me if you like it :)
***/

function slideSwitch() {
    var $active = jQuery('#slideshow IMG.active');

    if ( $active.length == 0 ) $active = jQuery('#slideshow IMG:last');

    // use this to pull the images in the order they appear in the markup
    var $next =  $active.next().length ? $active.next()
        : jQuery('#slideshow IMG:first');

    // uncomment the 3 lines below to pull the images in random order
    
    // var $sibs  = $active.siblings();
    // var rndNum = Math.floor(Math.random() * $sibs.length );
    // var $next  = $( $sibs[ rndNum ] );


    $active.addClass('last-active');

    $next.css({opacity: 0.0})
        .addClass('active')
        .animate({opacity: 1.0}, 1000, function() {
            $active.removeClass('active last-active');
        });
}

jQuery(function() {
    setInterval( "slideSwitch()", 5000 );
});

</script>

<style type="text/css">

/*** set the width and height to match your images **/

#slideshow {
    position:relative;
    height:300px;
	width:590px;
}

#slideshow IMG {
    position:absolute;
    top:0;
    left:0;
    z-index:8;
    opacity:0.0;
}

#slideshow IMG.active {
    z-index:10;
    opacity:1.0;
}

#slideshow IMG.last-active {
    z-index:9;
}

</style>

<div id="slideshow">

<?php
if (!empty($ashoppath) && is_dir("$ashoppath/slideshow") && !empty($ashopurl)) {
	$findfile = opendir("$ashoppath/slideshow");
	$starttime = time();
	$imagenumber = 1;
	while ($foundfile = readdir($findfile)) {
		if  (time()-$starttime > 180) exit;
		if (!is_dir("$ashoppath/products/dimensions/$foundfile")  && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") {
			$fileinfo = pathinfo("$foundfile");
			$extension = strtolower($fileinfo["extension"]);
			if ($extension == "jpg" || $extension == "jpeg" || $extension == "gif" || $extension == "png") {
				echo "<img src=\"slideshow/$foundfile\" alt=\"Slideshow Image $imagenumber\"";
				if ($imagenumber == 1) echo " class=\"active\"";
				echo "/>\n";
				$imagenumber++;
			}
		}
	}
}
?>
</div>