<?php
// AShop
// Copyright 2011 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

include "config.inc.php";

if ($ecard_x && $ecard_y) {
	if ($endpoint == "true") {
		echo "<html><head><body><script language=\"JavaScript\">
		opener.document.fulfiloptionform$formnumber.necardtextright.value=$ecard_x;
		this.close();
		</script>
		</body>
		</html>";
	} else {
		echo "<html><head><body><script language=\"JavaScript\">
		opener.document.fulfiloptionform$formnumber.necardtexttop.value=$ecard_y;
		opener.document.fulfiloptionform$formnumber.necardtextleft.value=$ecard_x;
		this.close();
		</script>
		</body>
		</html>";
	}
	exit;
}

echo "<HTML> 
<HEAD> 
 <TITLE>Picture Viewer</TITLE> 
 <script language='javascript'> 
   var arrTemp=self.location.href.split(\"?\"); 
   var picUrl = (arrTemp.length>0)?arrTemp[1]:\"\"; 
   var NS = (navigator.appName==\"Netscape\")?true:false; 

     function FitPic() {
	   var ecardImage = document.getElementById(\"ecardimage\");
       iWidth = (NS)?window.innerWidth:document.body.clientWidth; 
       iHeight = (NS)?window.innerHeight:document.body.clientHeight; 
       iWidth = ecardImage.width - iWidth; 
       iHeight = ecardImage.height - iHeight; 
       window.resizeBy(iWidth+30, iHeight+10);
       self.focus(); 
     }; 
 </script> 
</HEAD> 
<BODY bgcolor=\"$itembgcolor\" onload='FitPic();' topmargin=\"0\"  
marginheight=\"0\" leftmargin=\"0\" marginwidth=\"0\">
<CENTER>
<form name=\"ecardform\" action=\"setimagestart.php\" method=\"post\">
 <input type=\"hidden\" name=\"formnumber\" value=\"$fnum\">
 <input type=\"hidden\" name=\"endpoint\" value=\"$endpoint\">
 <input type=\"image\" name=\"ecard\" src=\"ecards/$image\" border=0><br>
 <img id=\"ecardimage\" src=\"ecards/$image\" border=0>
</form>
</CENTER>
</BODY> 
</HTML>";
?>