<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Edit Purchase Order Comments";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Delete comment...
if ($delete && $yes) {
	@mysqli_query($db, "DELETE FROM emerchant_vendornotes WHERE id='$delete'");
	unset($delete);
}
if ($delete && $no) unset($delete);

// Add new comment...
if ($add) @mysqli_query($db, "INSERT INTO emerchant_vendornotes (note, reusable) VALUES ('$newcomment','1')");

// Get comments from database
$result = @mysqli_query($db, "SELECT * FROM emerchant_vendornotes WHERE reusable='1'");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Purchase Order");

if ($delete && !$yes) {
	$deleteresult = @mysqli_query($db, "SELECT * FROM emerchant_vendornotes WHERE id='$delete'");
	$commentrow = @mysqli_fetch_array($deleteresult);
	echo "<div align=\"center\" class=\"heading3\"><br>
        <span class=\"heading2\">Edit Quote Comments</span><br>
			<p class=\"heading3\">Are you sure you want to delete this comment?</p><table width=\"66%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\"#e0e0e0\"><tr><td class=\"sm\">{$commentrow["note"]}</td></tr></table><p><form action=\"editvendornotes.php\" method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"$delete\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"submit\" name=\"no\" value=\"No\"></form>";
}

if (!$delete && !$edit) {
	echo "<div align=\"center\" class=\"heading3\"><br>
        <span class=\"heading2\">Edit Purchase Order Comments</span><br>
	  <table width=\"95%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\"><form action=\"editvendornotes.php\" method=\"post\">
        <tr> 
		  <td class=\"sm\"><input type=\"text\" size=\"73\" name=\"newcomment\"><input type=\"submit\" name=\"add\" value=\"Add New Comment\"></td>
		</tr></form>
	  </table><br>
      <table width=\"95%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\"> 
          <td width=\"100\" class=\"heading3_wht\"><b class=\"heading3_wht\">Comment</b></td>
          <td width=\"20\">&nbsp;</td>
        </tr>";

	while ($row = @mysqli_fetch_array($result)) {
		echo "<tr bgcolor=\"#e0e0e0\"> 
		<td class=\"sm\">{$row["note"]}</td><td class=\"sm\" align=\"center\" width=\"20\"><a href=\"editvendornotes.php?delete={$row["id"]}\"><img src=\"images/icon_trash.gif\" alt=\"Delete this comment from the database!\" border=\"0\"></a></td></tr>";
	}
	echo "</table>";
}

if (($order && $vendor) || $edit) echo "<form action=\"purchaseorder.php\" method=\"get\"><input type=\"hidden\" name=\"order\" value=\"$order\"><input type=\"hidden\" name=\"vendor\" value=\"$vendor\"><input type=\"hidden\" name=\"edit\" value=\"$edit\"><input type=\"submit\" value=\"Back to Purchase Order\"></form>";
echo "</td>
  </tr>
</table>
$footer";
?>