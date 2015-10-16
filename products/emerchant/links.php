<?php
$popuplogincheck = TRUE;
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Links";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($action == "delete" && $id) {
	@mysqli_query($db, "DELETE FROM emerchant_links WHERE id='$id'");
	echo "<html><head>\n<script language=\"JavaScript\">
	opener.location.href='$ashopurl/emerchant/links.php?msg=deleted';
	this.close();
	</script>\n</head></html>";
	exit;
}

if ($name && $url) {
	$result = @mysqli_query($db, "INSERT INTO emerchant_links (name, url) VALUES ('$name', '$url')");
	echo "<html><head>\n<script language=\"JavaScript\">
	opener.location.href='$ashopurl/emerchant/links.php?msg=added';
	this.close();
	</script>\n</head></html>";
	exit;
}

if ($msg) {
	echo $header;
	emerchant_sidebar();
	echo "<td valign=\"top\">";
	emerchant_topbar("Links");
	if ($msg == "deleted") echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">The link has been deleted.</font></div>";
	else if ($msg == "added") echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">The link has been added.</font></div>";
	echo "</td></tr></table>$footer";
	exit;
}

echo "<html><head><title>Add/Delete Links - $ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
</head>
<body text=\"#000000\" topmargin=\"0\">
<div align=\"center\">
  <form name=\"form1\" method=\"post\" action=\"links.php\">
    <br>
    <table width=\"580\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
      <tr bgcolor=\"#808080\"> 
        <td class=\"formlabel\" colspan=\"2\" align=\"right\"> 
          <div align=\"center\" class=\"heading3_wht\">Create Link</div>
        </td>
      </tr>
      <tr> 
        <td class=\"formlabel\" bgcolor=\"#d0d0d0\" width=\"95\" align=\"right\">Description: 
        </td>
        <td class=\"sm\" bgcolor=\"#d0d0d0\" width=\"495\"> 
          <input type=\"text\" name=\"name\" maxlength=\"50\" size=\"50\" value=\"$name\">
        </td>
      </tr>
      <tr> 
        <td class=\"formlabel\" bgcolor=\"#c0c0c0\" align=\"right\" width=\"95\">URL: 
        </td>
        <td class=\"sm\" bgcolor=\"#c0c0c0\" width=\"495\"> 
          <input type=\"text\" name=\"url\" maxlength=\"255\" size=\"60\" value=\"$url\">
        </td>
      </tr>
      <tr align=\"center\"> 
        <td colspan=\"2\">
          <input type=\"submit\" name=\"Submit\" value=\"Save Changes\"> <input type=\"button\" name=\"Cancel\" onClick=\"window.close()\" value=\"Cancel/Exit\">
        </td>
      </tr>
    </table>
  </form>
    <br>
    <table width=\"580\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
      <tr bgcolor=\"#808080\"> 
        <td class=\"formlabel\" colspan=\"2\" align=\"right\"> 
          <div align=\"center\" class=\"heading3_wht\">Edit Links</div>
        </td>
      </tr>";
$result = @mysqli_query($db, "SELECT * FROM emerchant_links ORDER BY name");
while ($row = @mysqli_fetch_array($result)) {
	echo "
      <tr> 
        <td class=\"sm\" bgcolor=\"#d0d0d0\">{$row["name"]} (<a href=\"{$row["url"]}\">{$row["url"]}</a>)</td>
	    <td class=\"sm\" bgcolor=\"#d0d0d0\" align=\"right\">[<a href=\"links.php?action=delete&id={$row["id"]}\">Delete</a>]</td>
      </tr>";
}
echo "
    </table>
  </form>
</div>
</body>
</html>";
?>