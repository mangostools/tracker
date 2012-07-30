<?php

// Execute only if included by index.php
if(!defined('mainfile'))
    die();

//create&delete db revision tags
if(isset($_GET["new_db_tag"]) && !empty($_POST["new_tag"]))
  mysql_query("INSERT INTO $trackerdb.revision (name) VALUES (\"".mysql_real_escape_string($_POST["new_tag"])."\")");
if(isset($_GET["delete_db_tag"]))
  mysql_query("DELETE FROM $trackerdb.revision WHERE id =".$_GET["delete_db_tag"]);

//promote&demote_user
if(isset($_GET["promote_user"]) && isset($_POST["user"]))
  mysql_query("UPDATE $trackerdb.users SET power = 100 WHERE id = ".mysql_real_escape_string($_POST["user"]));
if(isset($_GET["demote_user"]) && isset($_POST["user"]) && $_POST["user"] != $_SESSION["id"])
  mysql_query("UPDATE $trackerdb.users SET power = 1 WHERE id = ".mysql_real_escape_string($_POST["user"]));

?>
<fieldset><legend>Change Password</legend>
    <?php
    if(mysql_result(mysql_query("SELECT password FROM $trackerdb.users WHERE id =".$_SESSION["id"]),0)==md5("tracker"))
    {
        echo "<h2 style=color:red>You are using the standard password \"tracker\". Change it!</h2>";
    }
    ?>

    <form action=index.php?admin_start&change_pwd method=post>
        <tt>Password:      </tt> <input name=password type=password style="width:250px"></br>
        <tt>Password again:</tt> <input name=password2 type=password style="width:250px"></br>
        <input type=submit>
    </form>
</fieldset>



<fieldset><legend>Database Revision Tags</legend>
    Here you can create and delete DB revision tags like "1.0.0-dev". Deleting tags makes entries tagged for that DB revision invisible in the tracker.
    <form action=index.php?admin_start&new_db_tag method=post>
        <tt>New tag:      </tt> <input name=new_tag style="width:250px"><input type=submit value="Create new">
    </form>
    Current Tags:
    <ul>
    <?php
    $sql = mysql_query("SELECT id, name FROM $trackerdb.revision ORDER BY id DESC");
    while ($row = mysql_fetch_assoc($sql))
      echo "<li>".$row["name"]." (<a href=index.php?admin_start&delete_db_tag=".$row["id"].">delete</a>)</li>";
    ?>
    </ul>
</fieldset>


<fieldset><legend>Admin Users</legend>
    You can give other users access to this menu (or take it away). Woohoo, power!!
    <form action=index.php?admin_start&promote_user method=post>
        <tt>Pick a user</tt> <select name=user>
            <?php
            $sql= mysql_query("SELECT id, name FROM $trackerdb.users WHERE power = 1 ORDER BY id");
            while ($row = mysql_fetch_assoc($sql))
                echo "<option value=\"".$row["id"]."\">".$row["name"]."</option>";
            ?>
        </select>
        <input type=submit value="Promote to Admin">
    </form>
    <form action=index.php?admin_start&demote_user method=post>
        <tt>Pick an admin</tt> <select name=user>
            <?php
            $sql= mysql_query("SELECT id, name FROM $trackerdb.users WHERE power = 100 AND id != ".$_SESSION["id"]." ORDER BY id");
            while ($row = mysql_fetch_assoc($sql))
                echo "<option value=\"".$row["id"]."\">".$row["name"]."</option>";
            ?>
        </select>
        <input type=submit value="Demote to user">
    </form>


</fieldset>