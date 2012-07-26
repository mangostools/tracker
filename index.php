<?php
require_once("config.php");
$map = !isset($_GET['map']) ? "x" : $_GET['map'];
$areasort = empty($_GET['areasort']) ? "x" : $_GET['areasort'];
$areasort = ($map == "u") ? 0 : $areasort;
$offset = empty($_GET['offset']) ? 0 : $_GET['offset'];
$quest = empty($_GET['quest']) ? "x" : $_GET['quest'];
$query = empty($_POST['query']) ? "Title or ID" : $_POST['query'];

function id2nick($id) {
    global $trackerdb;
    if ($id > 0)
        return mysql_result(mysql_query("SELECT name FROM $trackerdb.users WHERE id=$id"), 0);
    else
        return "Unknown";
}

function id2name($table, $id) {
    global $mangosdb;
    return mysql_result(mysql_query("SELECT name FROM $mangosdb.$table WHERE entry=" . $id), 0);
}

$zos_profession = "-24,-101,-121,-181,-182,-201,-264,-304,-324";
$zos_class = "-61,-81,-82,-141,-161,-162,-261,-262,-263";

$quest_flags = Array(
    "QUEST_FLAGS_NONE", //0
    "QUEST_FLAGS_STAY_ALIVE", //1
    "QUEST_FLAGS_PARTY_ACCEPT", //2
    "QUEST_FLAGS_EXPLORATION", //4
    "QUEST_FLAGS_SHARABLE", //8
    "QUEST_FLAGS_NONE2", //16
    "QUEST_FLAGS_EPIC", //32
    "QUEST_FLAGS_RAID", //64
    "QUEST_FLAGS_UNK1", //128
    "QUEST_FLAGS_UNK2", //256
    "QUEST_FLAGS_HIDDEN_REWARDS", //512
    "QUEST_FLAGS_AUTO_REWARDED", //1024
);

$special_flags = Array(
    "NONE", //0
    "REPEATABLE", //1
    "REQUIRES_TRIGGER", //2
    "RESERVED", // 4
    "QUEST_SPECIAL_FLAG_DELIVER", // 8
    "QUEST_SPECIAL_FLAG_SPEAKTO", // 16
    "QUEST_SPECIAL_FLAG_KILL_OR_CAST", // 32
    "QUEST_SPECIAL_FLAG_TIMED", // 64
);

$problems = Array(
    "No Problem", //0
    "No Quest Start", //1
    "No Quest End" //2
);

$status = Array(
    "Unknown", //0
    "Cot completable", //1
    "Core issue", //2
    "Script issue", //3
    "DB issue", //4
    "Completable", //5
    "Blizz-like", //6
    "Obsolete" //7
);

$filter_status = !empty($_GET["filterstatus"]) ? $_GET["filterstatus"] : "any";

$statuscolor = Array(
    "black",
    "red",
    "brown",
    "orange",
    "darkcyan",
    "green",
    "blue",
    "gray");

$database_version = Array("1.0.0-dev");
$c_database_version = count($database_version) - 1;
$show_data_for_rev = isset($_GET["showrev"]) ? $_GET["showrev"] : "any";

mysql_connect($server, $user, $password, $mangosdb);
mysql_select_db($mangosdb);

//Login Stuff
$login = "<a href=index.php?login>Log In</a> | <a href=index.php?reg>Register</a>";
session_start();


if (isset($_GET["dologout"])) {
    unset($_SESSION);
}
if (isset($_GET["deletepost"]) && is_numeric($_GET["deletepost"])) {
    $u = mysql_result(mysql_query("SELECT user FROM $trackerdb.status WHERE id =" . $_GET["deletepost"]), 0);
    if ($u == $_SESSION["id"])
        mysql_query("DELETE FROM $trackerdb.status WHERE id = " . $_GET["deletepost"]);
}
if (isset($_GET["doreport"]) && is_numeric($quest) && isset($_POST["rev"]) && $_POST["rev"] != -1 && !empty($_POST["report"]) && isset($_SESSION["id"])) {
    mysql_query("INSERT INTO $trackerdb.status (quest_id, user, dbver, status, report, ts) VALUES ($quest, " . $_SESSION["id"] . ", \"" . mysql_real_escape_string($_POST["rev"]) . "\", \"" . mysql_real_escape_string($_POST["status"]) . "\", \"" . mysql_real_escape_string($_POST["report"]) . "\", " . time() . " )") or die(mysql_error());
}


if (isset($_GET["doreg"])) {
    if (!empty($_POST["username"]) && !empty($_POST["password"]) && !empty($_POST["password2"]) && strlen($_POST["username"]) > 3 && strlen($_POST["password"]) > 3 && $_POST["password"] == $_POST["password2"]) {
        mysql_query("INSERT INTO $trackerdb.users (`name`, `password`, `power`) VALUES (\"" . mysql_real_escape_string($_POST["username"]) . "\", \"" . md5($_POST["password"]) . "\", 1)") or die(mysql_error());
        $_GET["dologin"] = 1;
    }
}
if (isset($_GET["reg"])) {
    echo "<form action=index.php?doreg method=post>
	  <center><fieldset style=width:300px><legend>Registration</legend>
	  <tt>Username:      </tt> <input name=username style=\"width:250px\"></br>
	  <tt>Password:      </tt> <input name=password type=password style=\"width:250px\">
	  <tt>Password again:</tt> <input name=password2 type=password style=\"width:250px\">
	  <center><input type=submit></center>
	  </fieldset></center></form>";
    die();
}


if (isset($_GET["dologin"])) {
    if (isset($_POST["username"]) && isset($_POST["password"])) {
        $salt = time();
        if (mysql_result(mysql_query("SELECT COUNT(id) FROM $trackerdb.users WHERE name=\"" . mysql_real_escape_string($_POST["username"]) . "\" AND password=\"" . md5($_POST["password"]) . "\""), 0) == 1) {
            $_SESSION["id"] = mysql_result(mysql_query("SELECT id FROM $trackerdb.users WHERE name=\"" . mysql_real_escape_string($_POST["username"]) . "\" AND password=\"" . md5($_POST["password"]) . "\""), 0);
            $_SESSION["hash"] = md5(md5($_POST["password"]) . $salt);
            mysql_query("UPDATE $trackerdb.users SET lastlogin=" . $salt . " WHERE id =" . $_SESSION["id"]);
            $login = "Success";
        } else {
            echo "<center><font color=red>Login Failed!</font></center>";
            $_GET["login"] = 1;
        }
    }
}
if (isset($_GET["login"])) {
    echo "<form action=index.php?dologin method=post>
	  <center><fieldset style=width:300px><legend>Log In</legend>
	  <tt>Username:</tt> <input name=username style=\"width:250px\"></br>
	  <tt>Password:</tt> <input name=password type=password style=\"width:250px\">
	  <center><input type=submit></center>
	  </fieldset></center></form>";
    die();
}

if (isset($_SESSION["id"]) && $_SESSION["id"] != 0) {
    $sql = mysql_query("SELECT COUNT(id) FROM $trackerdb.users WHERE id=" . $_SESSION["id"] . " AND MD5(CONCAT( `password` , `lastlogin` )) = \"" . $_SESSION["hash"] . "\"") or die(mysql_error());
    if (mysql_result($sql, 0) == 1)
        $login = "Logged in as " . mysql_result(mysql_query("SELECT name FROM $trackerdb.users WHERE id=" . $_SESSION["id"]), 0) . " (<a href=index.php?dologout>Log Out</a>)";
    else
        unset($_SESSION);
}
?>
<html>
    <head><title>&quot;mangos-zero&quot; Quest Tracker - <?php echo mysql_result(mysql_query("SELECT version FROM $mangosdb.db_version"), 0); ?></title>
        <script type="text/javascript" src="http://static.wowhead.com/widgets/power.js"></script>
        <style>
            body, td, div { font-family:Helvetica,Arial,sans-serif; font-size:12px;}
            ul,li{margin:2px}
            legend{font-weight:bold;text-decoration:underline;font-size:13px;}
            table.main
            {
                border:1px #888 solid;
            }
            table.main tr td
            {
                border:1px #888 solid;
                padding: 4px 2px;
            }
            td {border:0px red dotted;}
            .breadcrumbs{background-color:#ddd;
                         border:1px #888 solid;
                         padding: 4px 2px;}
            .login{background-color:#ddd;
                   border:1px #888 solid;
                   padding: 4px 2px;
                   text-align:right;
            }
            .bar{width:100px;height:12px;padding:0;margin:0 5px;border:1px black solid;background-color:red;float:left}
            .green{height:12px;padding:0;margin:0;border:0 black solid;background-color:green}
            .tag0 {background-color:black;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag1 {background-color:red;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag2 {background-color:brown;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag3 {background-color:orange;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag4 {background-color:darkcyan;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag5 {background-color:green;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag6 {background-color:blue;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}
            .tag7 {background-color:#777;color:white;padding:1px 3px;border-radius: 5px;-moz-border-radius: 5px;}


        </style>
    </head>
    <body>
        <table cellspacing=0 style="width:99%" <?php if (!is_numeric($quest))
    echo "class=main"; ?>>
               <?php
               $search_form = "<form action=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&search method=post style=\"margin:0px;display:inline;\"><input name=query style=width:150px value=\"$query\"><input type=submit value=\"Search\"></form>";

               $select_status = "<form action=index.php method=get style=\"margin:0px;display:inline;\"><input type=hidden name=showrev value=$show_data_for_rev><select name=filterstatus><option value=any " . ($filter_status == "any" ? "selected=selected" : "") . ">any status</option>";
               for ($i = 1; $i < count($status); $i++)
                   $select_status.="<option value=" . $i . " " . (is_numeric($filter_status) && $i == $filter_status ? "selected=selected" : "") . " style=color:$statuscolor[$i]>" . $status[$i] . "</option>";
               $select_status.="</select><input type=submit value=\"Change\"></form>  ";

               $select_rev = "<form action=index.php method=get style=\"margin:0px;display:inline;\"><input type=hidden name=filterstatus value=$filter_status><select name=showrev><option value=any " . ($show_data_for_rev == "any" ? "selected=selected" : "") . ">any revision</option>";
               for ($i = 0; $i < count($database_version); $i++)
                   $select_rev.="<option value=" . $database_version[$i] . " " . ($database_version[$i] == $show_data_for_rev ? "selected=selected" : "") . ">" . $database_version[$i] . "</option>";
               $select_rev.="</select><input type=submit value=\"Change\"></form>  ";
               $login = $search_form . " | " . $select_status . " | " . $select_rev . " | <a href=index.php?recent&showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . ">Recent entries</a> | <a href=index.php?problems&showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . ">Obvious Problems</a> | " . $login;
               echo "<tr><td class=breadcrumbs colspan=2><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . ">root</a>&nbsp;>>&nbsp;";
               if (isset($_GET["search"])) {
                   echo "Search results</td><td class=login>$login</td></tr>";
                   if (is_numeric($query)) {
                       $count = mysql_result(mysql_query("SELECT COUNT(entry) FROM $mangosdb.quest_template WHERE entry = " . mysql_real_escape_string($query)), 0);
                       if ($count == 0) {
                           echo "<tr><td colspan=4>No entries found</td></tr>";
                           continue;
                       } else {
                           echo "<tr><td>Quest ID</td><td>Name</td><td>Reported Status</td></tr>";
                           $sql = mysql_query("SELECT entry, Title FROM $mangosdb.quest_template WHERE entry = " . mysql_real_escape_string($query));
                           while ($row = mysql_fetch_assoc($sql)) {
                               $res2 = mysql_query("SELECT status, dbver FROM $trackerdb.status WHERE quest_id = " . $row["entry"] . " AND dbver>=" . $database_version[$c_database_version] . " GROUP BY status ASC, dbver DESC");
                               $queststatus = "";
                               while ($row2 = mysql_fetch_array($res2))
                                   $queststatus.="<span style=color:" . $statuscolor[$row2["status"]] . ">" . $status[$row2["status"]] . " in " . $row2["dbver"] . "</span>; ";
                               if (empty($queststatus))
                                   $queststatus = $status[0] . " in " . $database_version[0];
                               echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["entry"] . "</a></td><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["Title"] . "</a></td><td>" . $queststatus . "</td></tr>";
                           }
                       }
                   }
                   else {
                       if (strlen($query) < 3) {
                           echo "<tr><td colspan=4>Minimal length of query is 3 characters... Don't even bother searching for a single \"a\"</td></tr>";
                           continue;
                       }

                       $count = mysql_result(mysql_query("SELECT COUNT(entry) FROM $mangosdb.quest_template WHERE Title LIKE \"%" . mysql_real_escape_string($query) . "%\""), 0);
                       if ($count == 0) {
                           echo "<tr><td colspan=4>No entries found</td></tr>";
                           continue;
                       } else {
                           echo "<tr><td>Quest ID</td><td>Name</td><td>Reported Status</td></tr>";
                           $sql = mysql_query("SELECT entry, Title FROM $mangosdb.quest_template WHERE Title LIKE \"%" . mysql_real_escape_string($query) . "%\"");
                           while ($row = mysql_fetch_assoc($sql)) {
                               $res2 = mysql_query("SELECT status, dbver FROM $trackerdb.status WHERE quest_id = " . $row["entry"] . " AND dbver>=" . $database_version[$c_database_version] . " GROUP BY status ASC, dbver DESC");
                               $queststatus = "";
                               while ($row2 = mysql_fetch_array($res2))
                                   $queststatus.="<span style=color:" . $statuscolor[$row2["status"]] . ">" . $status[$row2["status"]] . " in " . $row2["dbver"] . "</span>; ";
                               if (empty($queststatus))
                                   $queststatus = $status[0] . " in " . $database_version[0];
                               echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["entry"] . "</a></td><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["Title"] . "</a></td><td>" . $queststatus . "</td></tr>";
                           }
                       }
                   }
               }
               elseif (isset($_GET["problems"])) {
                   $count = mysql_result(mysql_query("SELECT COUNT(entry) FROM $trackerdb.problems WHERE entry NOT IN (SELECT quest_id FROM $trackerdb.status)"), 0);
                   echo "Obvious Problems ($count problems detected)</td><td class=login colspan=2>$login</td></tr>";
                   echo "<tr><td colspan=4>This page lists all quests that have issues which can be detected automatically but need to be diagnosed manually (you understand the dilemma, don't you?) and have not been looked at yet.<br>
        List of detectable problems (Please post ideas at the UDB Forums!!):<ul>";
                   for ($i = 1; $i < count($problems); $i++)
                       echo "<li>" . $problems[$i] . "</li>";
                   echo "</td></tr>";
                   if ($offset == "x")
                       $offset = 0;
                   echo "<tr><td colspan=2>Quest</td><td colspan=2>Problems</td></tr>";
                   if ($count == 0) {
                       echo "<tr><td colspan=4>No entries found</td></tr>";
                       continue;
                   }
                   $sql = mysql_query("SELECT entry, problem FROM $trackerdb.problems WHERE entry NOT IN (SELECT quest_id FROM $trackerdb.status) ORDER BY entry ASC LIMIT $offset, 50");
                   while ($row = mysql_fetch_array($sql)) {
                       echo "<tr>";
                       echo "<td colspan=2><a href=index.php?showrev=$show_data_for_rev&filterstatus=$filter_status&quest=" . $row["entry"] . ">" . $row["entry"] . " - " . mysql_result(mysql_query("SELECT Title FROM $mangosdb.quest_template WHERE entry =" . $row["entry"]), 0) . "</a></td>";
                       echo "<td colspan=2>";
                       for ($i = 0; $i < count($problems); $i++)
                           if ($row["problem"] & 1 << $i)
                               echo $problems[$i + 1] . "; ";
                       echo "</td>";
                       echo "</tr>";
                   }
                   echo "<tr><td colspan=4>Page: ";
                   for ($i = 0; $i < $count; $i+=50)
                       echo "<a href=index.php?showrev=$show_data_for_rev&problems&offset=$i>" . ($i / 50 + 1) . "</a> ";
                   echo "</td></tr>";
               }
               elseif (isset($_GET["recent"])) {
                   echo "Recent Entries</td><td class=login colspan=2>$login</td></tr>";

                   if ($offset == "x")
                       $offset = 0;
                   $dbver = $show_data_for_rev == "any" ? "" : " AND dbver = " . $show_data_for_rev;
                   $filter = $filter_status == "any" ? "" : " AND status = " . $filter_status;
                   $count = mysql_result(mysql_query("SELECT COUNT(id) FROM $trackerdb.status WHERE 1 $dbver $filter"), 0);
                   echo "<tr><td>Quest</td><td>User</td><td>Status</td><td>Report</td></tr>";
                   if ($count == 0) {
                       echo "<tr><td colspan=4>No entries found</td></tr>";
                       continue;
                   }
                   $sql = mysql_query("SELECT quest_id, dbver, user, report, status, ts FROM $trackerdb.status WHERE 1 $dbver $filter ORDER BY ts DESC LIMIT $offset, 50");
                   while ($row = mysql_fetch_array($sql)) {
                       echo "<tr>";
                       echo "<td><a href=index.php?showrev=$show_data_for_rev&quest=" . $row["quest_id"] . ">" . $row["quest_id"] . " - " . mysql_result(mysql_query("SELECT Title FROM $mangosdb.quest_template WHERE entry =" . $row["quest_id"]), 0) . "</a></td>";
                       echo "<td>" . mysql_result(mysql_query("SELECT name FROM $trackerdb.users WHERE id =" . $row["user"]), 0) . "</td>";
                       echo "<td><span style=color:" . $statuscolor[$row["status"]] . ">" . $status[$row["status"]] . "</span> in " . $row["dbver"] . "</td>";
                       echo "<td>" . nl2br($row["report"]) . "</br><i>" . date("d.m.Y H:i:s", $row["ts"]) . "</td>";
                       echo "</tr>";
                   }
                   echo "<tr><td colspan=4>Page: ";
                   for ($i = 0; $i < $count; $i+=50)
                       echo "<a href=index.php?showrev=$show_data_for_rev&recent&offset=$i>" . ($i / 50 + 1) . "</a> ";
                   echo "</td></tr>";
               } elseif ($map == "x" && $areasort == "x" && $quest == "x") {
                   echo "Map Selection</td><td class=login colspan=2>$login</td></tr>";
                   echo "<tr><td colspan=4 style=background-color:#eee>Total Counts</td></tr>";
                   $anz = mysql_result(mysql_query("SELECT count(entry) from $mangosdb.quest_template"), 0);
                   $unknown = $anz - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                   ;
                   $temp = "";
                   $working = array();
                   for ($i = 1; $i < count($status); $i++) {
                       $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       if ($working[$i] != 0)
                           $temp .=" - <font color=" . $statuscolor[$i] . ">" . $working[$i] . " " . $status[$i] . "</font>";
                   }
                   $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . " unknown</font>" . $temp;
                   $percent_completable = round((($working[5] + $working[6]) / $anz) * 100, 2);
                   $percent_not_completable = round(($working[1] / $anz) * 100, 2);
                   $percent_unknown = 100 - $percent_not_completable - $percent_completable;
                   echo "<tr><td>All Quests</td><td>$anz total</td><td>" . $temp . "</td><td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td></tr>";


                   $sql = "SELECT count( mq.entry ) as anz , ua.map, um.name, um.type FROM $mangosdb.quest_template AS mq, $trackerdb.areatable AS ua, $trackerdb.map AS um WHERE mq.ZoneOrSort >0 AND mq.ZoneOrSort = ua.id AND ua.map = um.id GROUP BY um.id ASC ORDER BY um.type, ua.map ASC";
                   $type = -1;
                   $result = mysql_query($sql) or die(mysql_error());
                   while ($row = mysql_fetch_assoc($result)) {
                       if ($type != $row["type"]) {
                           $type = $row["type"];
                           switch ($type) {
                               case 0:echo "<tr><td colspan=4 style=background-color:#eee>Map</td></tr>";
                                   break;
                               case 1:echo "<tr><td colspan=4 style=background-color:#eee>Instance</td></tr>";
                                   break;
                               case 2:echo "<tr><td colspan=4 style=background-color:#eee>Raid</td></tr>";
                                   break;
                               case 3:echo "<tr><td colspan=4 style=background-color:#eee>BG</td></tr>";
                                   break;
                               default:break;
                           }
                           echo "<tr><td>Map Name</td><td>Quests total</td><td>Trac Progress (<font color=black>unk</font>/<font color=red>bug</font>/<font color=brown>core</font>/<font color=orange>script</font>/<font color=darkcyan>DB</font>/<font color=green>ok</font>/<font color=blue>blizzlike</font>)</td><td>working</td></tr>";
                       }
                       if (is_numeric($filter_status)) {
                           $working = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (SELECT id FROM $trackerdb.areatable WHERE map = " . $row["map"] . ")) AND status = $filter_status AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           if ($working != 0)
                               echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=" . $row["map"] . ">" . $row["name"] . "</a></td><td>" . $row["anz"] . "</td><td><font color=" . $statuscolor[$filter_status] . ">" . $working . "</font></td></tr>";
                       }
                       else {
                           $temp = "";
                           $unknown = $row["anz"] - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (SELECT id FROM $trackerdb.areatable WHERE map = " . $row["map"] . ")) AND status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           ;
                           $working = array();
                           for ($i = 1; $i < count($status); $i++) {
                               $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (SELECT id FROM $trackerdb.areatable WHERE map = " . $row["map"] . ")) AND status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                               if ($working[$i] != 0)
                                   $temp .="/<font color=" . $statuscolor[$i] . ">" . $working[$i] . "</font>";
                           }
                           $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . "</font>" . $temp;
                           $percent_completable = round((($working[5] + $working[6]) / $row["anz"]) * 100, 2);
                           $percent_not_completable = round(($working[1] / $row["anz"]) * 100, 2);
                           $percent_unknown = 100 - $percent_not_completable - $percent_completable;
                           echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=" . $row["map"] . ">" . $row["name"] . "</a></td><td>" . $row["anz"] . "</td><td>$temp</td><td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td>";
                       }
                   }
                   echo "<tr><td colspan=4 style=background-color:#eee>Other</td></tr>";
                   echo "<tr><td>Name</td><td>Quests total</td><td>Trac Progress (<font color=black>unk</font>/<font color=red>bug</font>/<font color=brown>core</font>/<font color=orange>script</font>/<font color=darkcyan>DB</font>/<font color=green>ok</font>/<font color=blue>blizzlike</font>)</td><td>working</td></tr>";


                   $profession = mysql_result(mysql_query("SELECT COUNT(entry) AS anz FROM quest_template WHERE ZoneOrSort IN (" . $zos_profession . ")"), 0) or die(mysql_error());
                   if (is_numeric($filter_status)) {
                       $working = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (" . $zos_profession . ")) AND status = $filter_status AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       if ($working != 0)
                           echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=p>Profession</a></td><td>" . $profession . "</td><td><font color=" . $statuscolor[$filter_status] . ">" . $working . "</font></td></tr>";
                   }
                   else {
                       $temp = "";
                       $unknown = $profession - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (" . $zos_profession . ")) AND status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       ;
                       $working = array();
                       for ($i = 1; $i < count($status); $i++) {
                           $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (" . $zos_profession . ")) AND status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           if ($working[$i] != 0)
                               $temp .="/<font color=" . $statuscolor[$i] . ">" . $working[$i] . "</font>";
                       }
                       $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . "</font>" . $temp;
                       $percent_completable = round((($working[5] + $working[6]) / $profession) * 100, 2);
                       $percent_not_completable = round(($working[1] / $profession) * 100, 2);
                       $percent_unknown = 100 - $percent_not_completable - $percent_completable;

                       echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=p>Profession</a></td><td>" . $profession . "</td><td>$temp</td><td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td>";
                   }


                   $class = mysql_result(mysql_query("SELECT COUNT(entry) AS anz FROM quest_template WHERE ZoneOrSort <0 AND ZoneOrSort IN (" . $zos_class . ")"), 0) or die(mysql_error());
                   if (is_numeric($filter_status)) {
                       $working = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (" . $zos_class . ")) AND status = $filter_status AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       if ($working != 0)
                           echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=c>Class</a></td><td>" . $class . "</td><td><font color=" . $statuscolor[$filter_status] . ">" . $working . "</font></td></tr>";
                   }
                   else {
                       $temp = "";
                       $unknown = $class - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (" . $zos_class . ")) AND status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       ;
                       $working = array();
                       for ($i = 1; $i < count($status); $i++) {
                           $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort IN (" . $zos_class . ")) AND status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           if ($working[$i] != 0)
                               $temp .="/<font color=" . $statuscolor[$i] . ">" . $working[$i] . "</font>";
                       }
                       $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . "</font>" . $temp;
                       $percent_completable = round((($working[5] + $working[6]) / $class) * 100, 2);
                       $percent_not_completable = round(($working[1] / $class) * 100, 2);
                       $percent_unknown = 100 - $percent_not_completable - $percent_completable;
                       echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=c>Class</a></td><td>" . $class . "</td><td>$temp</td><td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td>";
                   }

                   $event = mysql_result(mysql_query("SELECT COUNT(entry) AS anz FROM quest_template WHERE ZoneOrSort <0 AND ZoneOrSort NOT IN (" . $zos_class . "," . $zos_profession . ")"), 0) or die(mysql_error());
                   if (is_numeric($filter_status)) {
                       $working = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort <0 AND ZoneOrSort NOT IN (" . $zos_class . "," . $zos_profession . ")) AND status = $filter_status AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       if ($working != 0)
                           echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=e>Event</a></td><td>" . $event . "</td><td><font color=" . $statuscolor[$filter_status] . ">" . $working . "</font></td></tr>";
                   }
                   else {
                       $temp = "";
                       $unknown = $event - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort <0 AND ZoneOrSort NOT IN (" . $zos_class . "," . $zos_profession . ")) AND status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       ;
                       $working = array();
                       for ($i = 1; $i < count($status); $i++) {
                           $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort <0 AND ZoneOrSort NOT IN (" . $zos_class . "," . $zos_profession . ")) AND status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           if ($working[$i] != 0)
                               $temp .="/<font color=" . $statuscolor[$i] . ">" . $working[$i] . "</font>";
                       }
                       $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . "</font>" . $temp;
                       $percent_completable = round((($working[5] + $working[6]) / $event) * 100, 2);
                       $percent_not_completable = round(($working[1] / $event) * 100, 2);
                       $percent_unknown = 100 - $percent_not_completable - $percent_completable;
                       echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=e>Event</a></td><td>" . $event . "</td><td>$temp</td><td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td>";
                   }

                   $other = mysql_result(mysql_query("SELECT COUNT(entry) AS anz FROM quest_template WHERE ZoneOrSort =0"), 0) or die(mysql_error());
                   if (is_numeric($filter_status)) {
                       $working = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort =0) AND status = $filter_status AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       if ($working != 0)
                           echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=u>Other/Unknown</a></td><td>" . $other . "</td><td><font color=" . $statuscolor[$filter_status] . ">" . $working . "</font></td></tr>";
                   }
                   else {
                       $temp = "";
                       $unknown = $other - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort =0) AND status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                       ;
                       $working = array();
                       for ($i = 1; $i < count($status); $i++) {
                           $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort =0) AND status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           if ($working[$i] != 0)
                               $temp .="/<font color=" . $statuscolor[$i] . ">" . $working[$i] . "</font>";
                       }
                       $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . "</font>" . $temp;
                       $percent_completable = round((($working[5] + $working[6]) / $other) * 100, 2);
                       $percent_not_completable = round(($working[1] / $other) * 100, 2);
                       $percent_unknown = 100 - $percent_not_completable - $percent_completable;
                       echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=u&areasort=0>Other/Unknown</a></td><td>" . $other . "</td><td>$temp</td><td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td></tr>";
                   }
               }
               if ($areasort == "x" && $quest == "x" && is_numeric($map) && mysql_result(mysql_query("SELECT COUNT(id) FROM $trackerdb.areatable WHERE map = $map"), 0) == 1)
                   $areasort = mysql_result(mysql_query("SELECT id FROM $trackerdb.areatable WHERE map = $map"), 0);

               if ($areasort === "x" && $quest == "x") {
                   if (is_numeric($map)) {
                       $mapname = mysql_result(mysql_query("SELECT name FROM $trackerdb.map WHERE id = $map"), 0);
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=$map>$mapname</a> >> Zone Selection</td><td class=login colspan=2>$login</td></tr>";
                       //zones
                       echo "<tr><td>Zone Name</td><td>Quests total</td><td>Trac Progress (<font color=black>unk</font>/<font color=red>bug</font>/<font color=brown>core</font>/<font color=orange>script</font>/<font color=darkcyan>DB</font>/<font color=green>ok</font>/<font color=blue>blizzlike</font>)</td><td>working</td></tr>";
                       $result = mysql_query("SELECT COUNT(m.entry) AS anz, m.ZoneOrSort,u.id, u.name FROM $mangosdb.quest_template as m, $trackerdb.areatable as u WHERE u.map=$map AND u.id = m.ZoneOrSort GROUP BY m.ZoneOrSort ASC") or die(mysql_error());
                   } elseif ($map == "p") {
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=p>Professions</a> >> Profession Selection</td><td class=login colspan=2>$login</td></tr>";
                       echo "<tr><td>Profession</td><td>Quests total</td><td>Trac Progress (<font color=black>unk</font>/<font color=red>bug</font>/<font color=brown>core</font>/<font color=orange>script</font>/<font color=darkcyan>DB</font>/<font color=green>ok</font>/<font color=blue>blizzlike</font>)</td><td>working</td></tr>";
                       //professions
                       $result = mysql_query("SELECT COUNT(m.entry) AS anz, m.ZoneOrSort,u.id, u.name FROM $mangosdb.quest_template as m, $trackerdb.questsort as u WHERE u.id = -1*m.ZoneOrSort AND m.ZoneOrSort IN (-24,-101,-121,-181,-182,-201,-264,-304,-324,-762,-371,-373) GROUP BY m.ZoneOrSort ASC") or die(mysql_error());
                   } elseif ($map == "c") {
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=c>Classes</a> >> Class Selection</td><td class=login colspan=2>$login</td></tr>";
                       echo "<tr><td>Class</td><td>Quests total</td><td>Trac Progress (<font color=black>unk</font>/<font color=red>bug</font>/<font color=brown>core</font>/<font color=orange>script</font>/<font color=darkcyan>DB</font>/<font color=green>ok</font>/<font color=blue>blizzlike</font>)</td><td>working</td></tr>";
                       $result = mysql_query("SELECT COUNT(m.entry) AS anz, m.ZoneOrSort,u.id, u.name FROM $mangosdb.quest_template as m, $trackerdb.questsort as u WHERE u.id = -1*m.ZoneOrSort AND m.ZoneOrSort IN (-61, -81, -82,-141,-161 , -162 ,-261 , -262 ,-263 , -372) GROUP BY m.ZoneOrSort ASC") or die(mysql_error());
                   } elseif ($map == "e") {
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=e>Event</a> >> Event Selection</td><td class=login colspan=2>$login</td></tr>";
                       echo "<tr><td>Event</td><td>Quests total</td><td>Trac Progress (<font color=black>unk</font>/<font color=red>bug</font>/<font color=brown>core</font>/<font color=orange>script</font>/<font color=darkcyan>DB</font>/<font color=green>ok</font>/<font color=blue>blizzlike</font>)</td><td>working</td></tr>";
                       $result = mysql_query("SELECT COUNT(m.entry) AS anz, m.ZoneOrSort,u.id, u.name FROM $mangosdb.quest_template as m, $trackerdb.questsort as u WHERE u.id = -1*m.ZoneOrSort AND m.ZoneOrSort NOT IN (-24,-101,-121,-181,-182,-201,-264,-304,-324,-762,-371,-373,-61, -81, -82,-141,-161 , -162 ,-261 , -262 ,-263 , -372) GROUP BY m.ZoneOrSort ASC") or die(mysql_error());
                   } elseif ($map == "u") {
                       die("You should not have been able to come here...");
                   }

                   while ($row = mysql_fetch_assoc($result)) {
                       if (is_numeric($filter_status)) {
                           $working = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort =" . $row["ZoneOrSort"] . ") AND status = $filter_status AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           if ($working != 0)
                               echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=p&areasort=" . $row["ZoneOrSort"] . ">" . $row["name"] . "</a></td><td>" . $row["anz"] . "</td><td><font color=" . $statuscolor[$filter_status] . ">" . $working . "</font></td></tr>";
                       }
                       else {
                           $temp = "";
                           $unknown = $row["anz"] - mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort =" . $row["ZoneOrSort"] . ") AND status > 0 AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                           $working = Array();
                           for ($i = 1; $i < count($status); $i++) {
                               $working[$i] = mysql_result(mysql_query("SELECT COUNT(DISTINCT quest_id) FROM $trackerdb.status WHERE quest_id in (SELECT entry FROM $mangosdb.quest_template WHERE ZoneOrSort =" . $row["ZoneOrSort"] . ") AND status = $i AND (dbver=" . (is_numeric($show_data_for_rev) ? $show_data_for_rev : "0 or dbver>0") . ")"), 0);
                               if ($working[$i] != 0)
                                   $temp .="/<font color=" . $statuscolor[$i] . ">" . $working[$i] . "</font>";
                           }
                           $temp = "<font color=" . $statuscolor[0] . ">" . $unknown . "</font>" . $temp;
                           $percent_completable = round((($working[5] + $working[6]) / $row["anz"]) * 100, 2);
                           $percent_not_completable = round(($working[1] / $row["anz"]) * 100, 2);
                           $percent_unknown = 100 - $percent_not_completable - $percent_completable;
                           echo "<tr>
			  <td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=$map&areasort=" . $row["ZoneOrSort"] . ">" . $row["name"] . "</a></td>
			  <td>" . $row["anz"] . "</td><td>$temp</td>
			  <td><span class=tag5 title=completable>$percent_completable %</span> <span class=tag1 title=\"not completable\">$percent_not_completable %</span> <span class=tag0 title=\"unknown\">$percent_unknown %</span></td></tr>";
                       }
                   }
               }
               elseif (is_numeric($areasort)) {
                   switch ($map) {
                       case "p":echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=p>Professions</a>&nbsp;>>&nbsp;";
                           break;
                       case "c":echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=c>Classes</a>&nbsp;>>&nbsp;";
                           break;
                       case "e":echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=e>Events</a>&nbsp;>>&nbsp;";
                           break;
                       case "u":echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=u>Unknown</a>&nbsp;>>&nbsp;";
                           break;
                       default: {
                               if ($areasort > 0) {
                                   $res = mysql_query("SELECT m.id, m.name FROM $trackerdb.map as m,$trackerdb.areatable as a WHERE m.id = a.map AND a.id=$areasort");
                                   $map = mysql_fetch_assoc($res);
                                   echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=" . $map["id"] . ">" . $map["name"] . "</a>&nbsp;>>&nbsp;";
                               }
                               if ($areasort < 0) {
                                   $mapname = in_array($areasort, explode(",", $zos_profession)) ? Array("Professions", "p") : (in_array($areasort, explode(",", $zos_class)) ? Array("Classes", "c") : Array("Events", "e"));
                                   echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=" . $mapname[1] . ">" . $mapname[0] . "</a>&nbsp;>>&nbsp;";
                               }
                           }
                           break;
                   }
                   if ($areasort > 0) {
                       $mapname = mysql_result(mysql_query("SELECT a.name FROM $trackerdb.areatable as a WHERE a.id=$areasort"), 0);
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&areasort=$areasort>$mapname</a>&nbsp;>>&nbsp;";
                   }
                   if ($areasort < 0) {
                       $mapname = mysql_result(mysql_query("SELECT a.name FROM $trackerdb.questsort as a WHERE a.id=-1*$areasort"), 0);
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&areasort=$areasort>$mapname</a>&nbsp;>>&nbsp;";
                   }
                   echo "</td><td class=login>$login</td></tr>";

                   $result = mysql_query("SELECT m.entry, m.Title FROM $mangosdb.quest_template as m WHERE m.ZoneOrSort =$areasort") or die(mysql_error());
                   while ($row = mysql_fetch_assoc($result)) {
                       $res2 = mysql_query("SELECT status, dbver FROM $trackerdb.status WHERE quest_id = " . $row["entry"] . " AND dbver>=" . $database_version[$c_database_version] . " GROUP BY status ASC, dbver DESC");
                       if (is_numeric($filter_status)) {
                           while ($row2 = mysql_fetch_array($res2)) {
                               if ($row2["status"] == $filter_status) {
                                   echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["entry"] . "</a></td><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["Title"] . "</a></td><td><span style=color:" . $statuscolor[$row2["status"]] . ">" . $status[$row2["status"]] . " in " . $row2["dbver"] . "</span></td></tr>";
                               }
                           }
                       } else {
                           $queststatus = "&nbsp;";
                           while ($row2 = mysql_fetch_array($res2))
                               $queststatus.="<span class=\"tag" . $row2["status"] . "\" title=\"" . $status[$row2["status"]] . "\">" . $row2["dbver"] . "</span> ";
                           echo "<tr><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["entry"] . "</a></td><td><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row["entry"] . ">" . $row["Title"] . "</a></td><td>" . $queststatus . "</td></tr>";
                       }
                   }
               } elseif (is_numeric($quest)) {
                   $row = mysql_fetch_assoc(mysql_query("SELECT * FROM $mangosdb.quest_template WHERE entry = $quest"));
                   $areasort = $row["ZoneOrSort"];
                   switch ($areasort) {
                       case 0:echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=u>Unknown</a>&nbsp;>>&nbsp;";
                           break;
                       case $row["ZoneOrSort"] > 0: {
                               $res = mysql_query("SELECT m.id, m.name FROM $trackerdb.map as m,$trackerdb.areatable as a WHERE m.id = a.map AND a.id=$areasort");
                               $map = mysql_fetch_assoc($res);
                               echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=" . $map["id"] . ">" . $map["name"] . "</a>&nbsp;>>&nbsp;";
                           }break;
                       case $row["ZoneOrSort"] < 0: {
                               $mapname = in_array($areasort, explode(",", $zos_profession)) ? Array("Professions", "p") : (in_array($areasort, explode(",", $zos_class)) ? Array("Classes", "c") : Array("Events", "e"));
                               echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&map=" . $mapname[1] . ">" . $mapname[0] . "</a>&nbsp;>>&nbsp;";
                           }
                           break;
                   }
                   if ($areasort > 0) {
                       $mapname = mysql_result(mysql_query("SELECT a.name FROM $trackerdb.areatable as a WHERE a.id=$areasort"), 0);
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&areasort=$areasort>$mapname</a>&nbsp;>>&nbsp;";
                   }
                   if ($areasort < 0) {
                       $mapname = mysql_result(mysql_query("SELECT a.name FROM $trackerdb.questsort as a WHERE a.id=-1*$areasort"), 0);
                       echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&areasort=$areasort>$mapname</a>&nbsp;>>&nbsp;";
                   }
                   echo "Quest " . $quest . " - " . $row["Title"] . "</td><td class=login>$login</td></tr>";
                   echo "<tr><td colspan=3>";
                   echo "<fieldset><legend>General Information</legend>";
                   echo "<b>Quest ID:</b> " . $row["entry"] . "</br>";
                   echo "<b>Title:</b> <a href=\"http://old.wowhead.com/quest=" . $quest . "\" target=_blank>" . htmlentities($row["Title"]) . "</a></br>";
                   $questtype = $row["Type"] == 0 ? "none" : mysql_result(mysql_query("SELECT name FROM $trackerdb.questinfo WHERE id=" . $row["Type"]), 0);
                   echo "<b>Quest Type:</b> " . $questtype . "</br>";
                   if ($row["SuggestedPlayers"] > 0)
                       echo "Suggested Players: " . $row["SuggestedPlayers"] . "</br>";
                   echo "<b>Quest Start:</b> ";
                   if (mysql_result(mysql_query("SELECT COUNT(id) FROM $mangosdb.creature_questrelation WHERE quest=" . $quest), 0) > 0) {
                       $creature_id = mysql_result(mysql_query("SELECT id FROM $mangosdb.creature_questrelation WHERE quest=" . $quest), 0);
                       echo mysql_result(mysql_query("SELECT name FROM $mangosdb.creature_template WHERE entry=" . $creature_id), 0) . " (NPC) <a href=\"http://old.wowhead.com/npc=" . $creature_id . "\" target=_blank>Wowhead</a>";
                   } else if (mysql_result(mysql_query("SELECT COUNT(id) FROM $mangosdb.gameobject_questrelation WHERE quest=" . $quest), 0) > 0)
                       echo mysql_result(mysql_query("SELECT name FROM $mangosdb.gameobject_template WHERE entry=(SELECT id FROM $mangosdb.gameobject_questrelation WHERE quest=" . $quest . ")"), 0) . " (GO)";
                   else if (mysql_result(mysql_query("SELECT COUNT(entry) FROM $mangosdb.item_template WHERE startquest=" . $quest), 0) > 0)
                       echo mysql_result(mysql_query("SELECT name FROM $mangosdb.item_template WHERE startquest=" . $quest), 0) . " (Item)";
                   else if (mysql_result(mysql_query("SELECT COUNT(id) FROM $mangosdb.game_event_creature_quest WHERE quest=" . $quest), 0) > 0) {
                       $creature_id = mysql_result(mysql_query("SELECT id FROM $mangosdb.game_event_creature_quest WHERE quest=" . $quest), 0);
                       echo mysql_result(mysql_query("SELECT name FROM $mangosdb.creature_template WHERE entry=" . $creature_id), 0) . " (NPC) during event " . mysql_result(mysql_query("SELECT description FROM $mangosdb.game_event WHERE entry=(SELECT event FROM $mangosdb.game_event_creature_quest WHERE quest=" . $quest . ")"), 0) . " <a href=\"http://old.wowhead.com/npc=" . $creature_id . "\" target=_blank>Wowhead</a>";
                   }
                   else
                       echo "---";
                   echo "</br>";
                   echo "<b>Quest End:</b> ";
                   if (mysql_result(mysql_query("SELECT COUNT(id) FROM $mangosdb.creature_involvedrelation WHERE quest=" . $quest), 0) > 0) {
                       $creature_id = mysql_result(mysql_query("SELECT id FROM $mangosdb.creature_involvedrelation WHERE quest=" . $quest), 0);
                       echo mysql_result(mysql_query("SELECT name FROM $mangosdb.creature_template WHERE entry=" . $creature_id), 0) . " (NPC) <a href=\"http://old.wowhead.com/npc=" . $creature_id . "\" target=_blank>Wowhead</a>";
                   } else if (mysql_result(mysql_query("SELECT COUNT(id) FROM $mangosdb.gameobject_involvedrelation WHERE quest=" . $quest), 0) > 0)
                       echo mysql_result(mysql_query("SELECT name FROM $mangosdb.gameobject_template WHERE entry=(SELECT id FROM $mangosdb.gameobject_involvedrelation WHERE quest=" . $quest . ")"), 0) . " (GO)";
                   else
                       echo "---";
                   echo "</br>";
                   if ($row["PointX"] != 0 && $row["PointY"] != 0)
                       echo "<b>Point of Interest:</b> " . mysql_result(mysql_query("SELECT name FROM $trackerdb.map WHERE id=" . $row["PointMapId"]), 0) . " (X:" . $row["PointX"] . " - Y:" . $row["PointY"] . ")</br>";
                   echo "</fieldset>";

                   echo "<fieldset><legend>Requirements</legend>";
                   echo "<b>Minimum Level:</b> " . $row["MinLevel"] . "</br>";
                   echo "<b>Quest Level:</b> " . $row["QuestLevel"] . "</br>";
                   if ($row["SkillOrClass"] > 0) {
                       $skill = mysql_result(mysql_query("SELECT name FROM $trackerdb.skillline WHERE id=" . $row["SkillOrClass"]), 0);
                       echo "<b>Required Skill:</b> " . $skill . " " . $row["RequiredSkillValue"] . "</br>";
                   }
                   if ($row["SkillOrClass"] < 0) {
                       $skill = mysql_result(mysql_query("SELECT name FROM $trackerdb.chrclasses WHERE id=-1*" . $row["SkillOrClass"]), 0);
                       echo "<b>Required Class:</b> " . $skill . "</br>";
                   }
                   echo "<b>Required Races:</b> ";
                   if ($row["RequiredRaces"] == 0)
                       $row["RequiredRaces"] = 254;
                   for ($i = 0; $i <= 8; $i++) {
                       if ($i != 8) {
                           if ($row["RequiredRaces"] & 1 << $i)
                               echo strtolower("<img src=\"images/race_" . str_replace(" ", "", mysql_result(mysql_query("SELECT name FROM $trackerdb.chrraces WHERE id=" . ($i + 1)), 0)) . "_male.jpg\" style=width:32px> ");
                           else
                               echo strtolower("<img src=\"images/race_" . str_replace(" ", "", mysql_result(mysql_query("SELECT name FROM $trackerdb.chrraces WHERE id=" . ($i + 1)), 0)) . "_male.jpg\" style=width:32px;opacity:0.3> ");
                       }
                   }
                   echo "</br>";
                   if ($row["RequiredMinRepFaction"] or $row["RequiredMinRepFaction"]) {
                       echo "<b>Required Faction Popularity:</b> ";
                       if ($row["RequiredMinRepFaction"])
                           echo mysql_result(mysql_query("SELECT name FROM $trackerdb.faction WHERE id=" . $row["RequiredMinRepFaction"]), 0) . " > " . $row["RequiredMinRepValue"] . "; ";
                       if ($row["RequiredMaxRepFaction"])
                           echo mysql_result(mysql_query("SELECT name FROM $trackerdb.faction WHERE id=" . $row["RequiredMaxRepFaction"]), 0) . " < " . $row["RequiredMaxRepValue"];
                       echo "</br>";
                   }

                   if ($row["QuestFlags"] > 0) {
                       echo "<b>Quest Flags:</b> ";
                       for ($i = 0; $i <= 15; $i++)
                           if ($row["QuestFlags"] & 1 << $i)
                               echo (1 << $i) . "=" . $quest_flags[$i + 1] . "; ";
                       echo "</br>";
                   }


                   if ($row["SpecialFlags"] > 0) {
                       echo "<b>Special Flags:</b> ";
                       for ($i = 0; $i <= 2; $i++)
                           if ($row["SpecialFlags"] & 1 << $i)
                               echo (1 << $i) . "=" . $special_flags[$i + 1] . "; ";
                       echo "</br>";
                   }

                   echo "</fieldset>";



                   echo "<fieldset><legend>Connected Quests</legend>";

                   $parent = $row["PrevQuestId"];
                   if ($parent != 0) {
                       $parent_active = false;
                       if ($parent < 0) {
                           $parent *= - 1;
                           $parent_active = true;
                       }
                       echo "<b>Previous quest:</b> <a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $parent . ">" . mysql_result(mysql_query("SELECT Title FROM $mangosdb.quest_template WHERE entry=" . $parent), 0) . " (" . $parent . ")</a>" . ($parent_active ? " (must be active)" : "") . "<br>";
                   }

                   $next = $row["NextQuestId"];
                   if ($next != 0) {
                       $subquest = false;
                       if ($next < 0) {
                           $next *= - 1;
                           $subquest = true;
                       }
                       echo "<b>Next quest:</b> <a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $next . ">" . mysql_result(mysql_query("SELECT Title FROM $mangosdb.quest_template WHERE entry=" . $next), 0) . " (" . $next . ")</a>" . ($subquest ? " (subquest)" : "") . "<br>";
                   }
                   $chain = $row["NextQuestInChain"];
                   if ($chain != 0) {
                       echo "<b>Quest Chain:</b> <a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $chain . ">" . mysql_result(mysql_query("SELECT Title FROM $mangosdb.quest_template WHERE entry=" . $chain), 0) . " (" . $chain . ")</a><br>";
                   }
                   $others = mysql_result(mysql_query("SELECT Count(entry) FROM $mangosdb.quest_template WHERE PrevQuestId=$quest OR PrevQuestId=-$quest"), 0);
                   if ($others > 0) {
                       echo "<b>Quests pointing to this:</b> <ul>";
                       $sql = mysql_query("SELECT entry, Title FROM $mangosdb.quest_template WHERE PrevQuestId=$quest OR PrevQuestId=-$quest ORDER BY entry");
                       while ($row2 = mysql_fetch_assoc($sql)) {
                           echo "<li><a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row2["entry"] . ">" . $row2["Title"] . " (" . $row2["entry"] . ")</a></li>";
                       }
                       echo "</ul>";
                   }
                   $exclusivegroup = $row["ExclusiveGroup"];
                   if (!empty($exclusivegroup)) {
                       echo "<b>Exclusive Group:</b> ";
                       $res = mysql_query("SELECT entry,Title FROM $mangosdb.quest_template WHERE ExclusiveGroup=$exclusivegroup");
                       $i = 0;
                       while ($row2 = mysql_fetch_array($res)) {
                           if ($i > 0) {
                               if ($exclusivegroup > 0)
                                   echo " OR ";
                               else
                                   echo " AND ";
                           }
                           echo "<a href=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=" . $row2["entry"] . ">" . $row2["Title"] . " (" . $row2["entry"] . ")</a>";
                           $i++;
                       }
                   }

                   echo "</fieldset>";

                   //On Quest Start
                   $temp = "";
                   if ($row["SrcItemId"] > 0 && $row["SrcItemCount"] > 0)
                       $temp.="<tr><td><b>Provided Item:</b> " . $row["SrcItemCount"] . "x " . mysql_result(mysql_query("SELECT name FROM $mangosdb.item_template WHERE entry=" . $row["SrcItemId"]), 0) . "</td></tr>";
                   if ($row["SrcSpell"] > 0)
                       $temp.="<tr><td><b>Spell:</b> " . mysql_result(mysql_query("SELECT name FROM $trackerdb.spell WHERE id=" . $row["SrcSpell"]), 0) . "</td></tr>";
                   if (!empty($temp)) {
                       echo "<fieldset><legend>On Quest Start</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset>";
                   }

                   echo "<fieldset><legend>Quest Texts</legend>";
                   if (!empty($row["Details"]))
                       echo "<p style=\"text-indent:-6em;margin-left:6em\"><b>Detail text:</b> <i>" . str_ireplace(array("\$b", "\$n", "\$r", "\$c", "\$g"), array("</br>", "&lt;Name&gt;", "&lt;Race&gt;", "&lt;Class&gt;", "&lt;Custom&gt;"), $row["Details"]) . "</i></p>";
                   if (!empty($row["Objectives"]))
                       echo "<p style=\"text-indent:-6em;margin-left:6em\"><b>Objectives text:</b> <i>" . str_ireplace(array("\$b", "\$n", "\$r", "\$c", "\$g"), array("</br>", "&lt;Name&gt;", "&lt;Race&gt;", "&lt;Class&gt;", "&lt;Custom&gt;"), $row["Objectives"]) . "</i></p>";
                   if (!empty($row["RequestItemsText"]))
                       echo "<p style=\"text-indent:-6em;margin-left:6em\"><b>Progress text:</b> <i>" . str_ireplace(array("\$b", "\$n", "\$r", "\$c", "\$g"), array("</br>", "&lt;Name&gt;", "&lt;Race&gt;", "&lt;Class&gt;", "&lt;Custom&gt;"), $row["RequestItemsText"]) . "</i></p>";
                   if (!empty($row["OfferRewardText"]))
                       echo "<p style=\"text-indent:-6em;margin-left:6em\"><b>Completion text:</b> <i>" . str_ireplace(array("\$b", "\$n", "\$r", "\$c", "\$g"), array("</br>", "&lt;Name&gt;", "&lt;Race&gt;", "&lt;Class&gt;", "&lt;Custom&gt;"), $row["OfferRewardText"]) . "</i></p>";
                   if (!empty($row["EndText"]))
                       echo "<p style=\"text-indent:-6em;margin-left:6em\"><b>End Text:</b> <i>" . str_ireplace(array("\$b", "\$n", "\$r", "\$c", "\$g"), array("</br>", "&lt;Name&gt;", "&lt;Race&gt;", "&lt;Class&gt;", "&lt;Custom&gt;"), $row["EndText"]) . "</i></p>";
                   if (!empty($row["CompletedText"]))
                       echo "<p style=\"text-indent:-6em;margin-left:6em\"><b>Completed Text:</b> <i>" . str_ireplace(array("\$b", "\$n", "\$r", "\$c", "\$g"), array("</br>", "&lt;Name&gt;", "&lt;Race&gt;", "&lt;Class&gt;", "&lt;Custom&gt;"), $row["CompletedText"]) . "</i></p>";
                   echo "</fieldset>";

                   //Objectives
                   echo "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr>";

                   //Item
                   $temp = "";
                   for ($i = 1; $i <= 6; $i++) {
                       if (!empty($row["ReqItemId" . $i]) && !empty($row["ReqItemCount" . $i])) {
                           $temp.="<tr><td>" . $row["ReqItemCount" . $i] . "x <a href=\"http://old.wowhead.com/item=" . mysql_result(mysql_query("SELECT entry FROM $mangosdb.item_template WHERE entry=" . $row["ReqItemId" . $i]), 0) . "\" target=_blank>" . mysql_result(mysql_query("SELECT name FROM $mangosdb.item_template WHERE entry=" . $row["ReqItemId" . $i]), 0) . "</a></td></tr>";
                       }
                       unset($row["ReqItemCount" . $i], $row["ReqItemId" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Required Items</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   //Creature or GO
                   $temp = "";
                   for ($i = 1; $i <= 4; $i++) {
                       if (!empty($row["ReqCreatureOrGOId" . $i]) && !empty($row["ReqCreatureOrGOCount" . $i])) {
                           $temp .= "<tr><td><b>" . ((empty($row["ReqSpellCast" . $i]) && $row["ReqCreatureOrGOId" . $i] > 0) ? "Kill " : "Use/Cast ") . ($row["ReqCreatureOrGOId" . $i] > 0 ? "Creature" : "Object") . ":</b> " . $row["ReqCreatureOrGOCount" . $i] . "x " . mysql_result(mysql_query("SELECT name FROM $mangosdb." . ($row["ReqCreatureOrGOId" . $i] > 0 ? "creature" : "gameobject") . "_template WHERE entry=" . ($row["ReqCreatureOrGOId" . $i] > 0 ? $row["ReqCreatureOrGOId" . $i] : -1 * $row["ReqCreatureOrGOId" . $i])), 0) . " " . (!empty($row["ObjectiveText" . $i]) ? "(=" . $row["ObjectiveText" . $i] . ")" : "") . "</td></tr>";
                       }
                       unset($row["ReqCreatureOrGOId" . $i], $row["ReqCreatureOrGOCount" . $i], $row["ObjectiveText" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Creature or GO</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }
                   //Source
                   $temp = "";
                   for ($i = 1; $i <= 4; $i++) {
                       if (!empty($row["ReqSourceId" . $i])) {
                           $temp .= "<tr><td>" . ($row["ReqSourceCount" . $i] > 0 ? $row["ReqSourceCount" . $i] . "x " : "unlimited ") . mysql_result(mysql_query("SELECT name FROM $mangosdb.item_template WHERE entry=" . $row["ReqSourceId" . $i]), 0) . "</td></tr>";
                       }
                       unset($row["ReqSourceId" . $i], $row["ReqSourceCount" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Source Items</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }
                   //Spell
                   $temp = "";
                   for ($i = 1; $i <= 4; $i++) {
                       if (!empty($row["ReqSpellCast" . $i])) {
                           $temp .= "<tr><td>" . mysql_result(mysql_query("SELECT name FROM $trackerdb.spell WHERE id=" . $row["ReqSpellCast" . $i]), 0) . "</td></tr>";
                       }
                       unset($row["ReqSpellCast" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Cast Spell</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   //Other
                   $temp = "";
                   if ($row["LimitTime"] > 0)
                       $temp.="<tr><td><b>Time Limit:</b> " . floor($row["LimitTime"] / 60) . ":" . ($row["LimitTime"] % 60 < 10 ? "0" : "") . ($row["LimitTime"] % 60) . "</td></tr>";
                   if ($row["RewOrReqMoney"] < 0)
                       $temp.="<tr><td><b>Money:</b> " . (-$row["RewOrReqMoney"] >= 10000 ? floor(-$row["RewOrReqMoney"] / 10000) . "g" : "")
                               . ((-$row["RewOrReqMoney"] % 10000) >= 100 ? floor((-$row["RewOrReqMoney"] % 10000) / 100) . "s" : "")
                               . ((-$row["RewOrReqMoney"] % 100) > 0 ? ((-$row["RewOrReqMoney"] % 100) . "c") : "")
                               . "</td></tr>";
                   if ($row["RepObjectiveFaction"] > 0 && $row["RepObjectiveValue"] > 0)
                       $temp.="<tr><td><b>Gain Reputation:</b> " . mysql_result(mysql_query("SELECT name FROM $trackerdb.faction WHERE id=" . $row["RepObjectiveFaction"]), 0) . " => " . $row["RepObjectiveValue"] . "</td></tr>";
                   if ($row["PlayersSlain"] > 0)
                       $temp.="<tr><td>Slay <b>" . $row["PlayersSlain"] . "</b> Enemy Players</td></tr>";

                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Other Requirements</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   echo "</tr></table>";



                   //Rewards
                   echo "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr>";

                   //Reward Reputation
                   $temp = "";
                   for ($i = 1; $i <= 5; $i++) {
                       if (!empty($row["RewRepFaction" . $i]) && (!empty($row["RewRepValue" . $i]) || !empty($row["RewRepValueId" . $i]) )) {
                           $temp.="<tr><td>" . mysql_result(mysql_query("SELECT name FROM $trackerdb.faction WHERE id=" . $row["RewRepFaction" . $i]), 0) . ": ";
                           $temp .= ( $row["RewRepValue" . $i] ? $row["RewRepValue" . $i] : "");
                           $temp .= ( ($row["RewRepValue" . $i] && $row["RewRepValueId" . $i]) ? " / " : "");
                           $temp .= ( $row["RewRepValueId" . $i] ? "<b>ID:</b> " . $row["RewRepValueId" . $i] : "") . "</td></tr>";
                       }
                       unset($row["RewRepFaction" . $i], $row["RewRepValue" . $i], $row["RewRepValueId" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Reputation reward</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   //Reward Choice
                   $temp = "";
                   for ($i = 1; $i <= 6; $i++) {
                       if (!empty($row["RewChoiceItemId" . $i]) && !empty($row["RewChoiceItemCount" . $i])) {
                           $temp.="<tr><td>" . $row["RewChoiceItemCount" . $i] . "x " . id2name("item_template", $row["RewChoiceItemId" . $i]) . "</td></tr>";
                       }
                       unset($row["RewChoiceItemCount" . $i], $row["RewChoiceItemId" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Item Choice</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   //Reward Item
                   $temp = "";
                   for ($i = 1; $i <= 4; $i++) {
                       if (!empty($row["RewItemId" . $i]) && !empty($row["RewItemCount" . $i])) {
                           $temp.="<tr><td>" . $row["RewItemCount" . $i] . "x " . id2name("item_template", $row["RewItemId" . $i]) . "</td></tr>";
                       }
                       unset($row["RewItemCount" . $i], $row["RewItemId" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Item Reward</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   //Reward Other
                   $temp = "";
                   if ($row["RewMoneyMaxLevel"] > 0)
                       $temp.="<tr><td><b>Money at max Level/XP: Raw:</b> " . $row["RewMoneyMaxLevel"] . "</td></tr>";
                   if ($row["RewXPId"] > 0)
                       $temp.="<tr><td><b>RewXPId: Raw:</b> " . $row["RewXPId"] . "</td></tr>";
                   if ($row["BonusTalents"] > 0)
                       $temp.="<tr><td><b>Talent points:</b> " . $row["BonusTalents"] . "</td></tr>";
                   if ($row["RewOrReqMoney"] > 0)
                       $temp.="<tr><td><b>Money:</b> " . ($row["RewOrReqMoney"] >= 10000 ? floor($row["RewOrReqMoney"] / 10000) . "g" : "")
                               . (($row["RewOrReqMoney"] % 10000) >= 100 ? floor(($row["RewOrReqMoney"] % 10000) / 100) . "s" : "")
                               . (($row["RewOrReqMoney"] % 100) > 0 ? (($row["RewOrReqMoney"] % 100) . "c") : "")
                               . "</td></tr>";
                   if ($row["RewSpellCast"] > 0 or $row["RewSpell"] > 0)
                       $temp.="<tr><td><b>Spell:</b> " . ($row["RewSpell"] > 0 ? mysql_result(mysql_query("SELECT name FROM $trackerdb.spell WHERE id=" . $row["RewSpell"]), 0) : "") . ($row["RewSpellCast"] > 0 && $row["RewSpell"] > 0 ? " -> " : "") . ($row["RewSpellCast"] > 0 ? "Cast: " . mysql_result(mysql_query("SELECT name FROM $trackerdb.spell WHERE id=" . $row["RewSpellCast"]), 0) : "") . "</td></tr>";
                   if ($row["CharTitleId"] > 0)
                       $temp.="<tr><td><b>Title:</b> " . mysql_result(mysql_query("SELECT name FROM $trackerdb.chartitles WHERE id=" . $row["CharTitleId"]), 0) . "</td></tr>";
                   if ($row["RewMailTemplateId"] > 0)
                       $temp.="<tr><td><b>Item by Mail:</b> " . mysql_result(mysql_query("SELECT name FROM $mangosdb.item_template WHERE entry=(SELECT item FROM $mangosdb.quest_mail_loot_template WHERE entry=" . $quest . ")"), 0) . " after " . floor($row["RewMailDelaySecs"] / 60) . ":" . ($row["RewMailDelaySecs"] % 60 < 10 ? "0" : "") . ($row["RewMailDelaySecs"] % 60) . "</td></tr>";

                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Other Rewards</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }

                   echo "</tr></table>";



                   //Emotes
                   echo "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr>";

                   //DetailEmote
                   $temp = "";
                   for ($i = 1; $i <= 4; $i++) {
                       if (!empty($row["DetailsEmote" . $i])) {
                           $emote = $row["DetailsEmote" . $i];
                           $emote_delay = $row["DetailsEmoteDelay" . $i];
                           $temp.="<tr><td>" . mysql_result(mysql_query("SELECT name FROM $trackerdb.emotes WHERE id=" . $row["DetailsEmote" . $i]), 0) . "(" . $row["DetailsEmote" . $i] . ") after " . $row["DetailsEmoteDelay" . $i] . "</td></tr>";
                       }
                       unset($row["DetailsEmote" . $i], $row["DetailsEmoteDelay" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Details Emotes</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }
                   //OfferRewardEmote
                   $temp = "";
                   for ($i = 1; $i <= 4; $i++) {
                       if (!empty($row["OfferRewardEmote" . $i])) {

                           $temp.="<tr><td>" . mysql_result(mysql_query("SELECT name FROM $trackerdb.emotes WHERE id=" . $row["OfferRewardEmote" . $i]), 0) . "(" . $row["OfferRewardEmote" . $i] . ") after " . $row["OfferRewardEmoteDelay" . $i] . "</td></tr>";
                       }
                       unset($row["OfferRewardEmote" . $i], $row["OfferRewardEmoteDelay" . $i]);
                   }
                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Offer Reward Emotes</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }
                   //Other Emotes
                   $temp = "";
                   if (!empty($row["IncompleteEmote"])) {
                       $temp.="<tr><td><b>Incomplete Emote:</b> " . mysql_result(mysql_query("SELECT name FROM $trackerdb.emotes WHERE id=" . $row["IncompleteEmote"]), 0) . "(" . $row["IncompleteEmote"] . ")</td></tr>";
                   }
                   if (!empty($row["CompleteEmote"])) {
                       $temp.="<tr><td><b>Complete Emote:</b> " . mysql_result(mysql_query("SELECT name FROM $trackerdb.emotes WHERE id=" . $row["CompleteEmote"]), 0) . "(" . $row["CompleteEmote"] . ")</td></tr>";
                   }

                   if (!empty($temp)) {
                       echo "<td valign=top><fieldset><legend>Other Emotes</legend><table cellspacing=2>";
                       echo $temp;
                       echo "</table></fieldset></td>";
                   }


                   echo "</tr></table>";


                   unset($row["entry"], $row["PointX"], $row["PointY"], $row["PointMapId"], $row["PlayersSlain"], $row["BonusTalents"], $row["SrcItemId"], $row["RewMailTemplateId"], $row["RewMailDelaySecs"], $row["RepObjectiveFaction"], $row["RepObjectiveValue"], $row["CharTitleId"], $row["SrcItemCount"], $row["SrcSpell"], $row["RequestItemsText"], $row["RewSpellCast"], $row["RewSpell"], $row["RewOrReqMoney"], $row["RewMoneyMaxLevel"], $row["OfferRewardText"], $row["QuestFlags"], $row["SpecialFlags"], $row["LimitTime"], $row["SuggestedPlayers"], $row["Details"], $row["Objectives"], $row["Title"], $row["MinLevel"], $row["QuestLevel"], $row["ZoneOrSort"], $row["Type"], $row["SkillOrClass"], $row["RequiredSkillValue"], $row["RequiredRaces"], $row["RequiredMinRepFaction"], $row["RequiredMaxRepFaction"], $row["RequiredMinRepValue"], $row["RequiredMaxRepValue"], $row["PrevQuestId"], $row["NextQuestId"], $row["NextQuestInChain"], $row["ExclusiveGroup"], $row["RewXPId"], $row["CompletedText"], $row["EndText"], $row["IncompleteEmote"], $row["CompleteEmote"]);

                   $temp = "";
                   $n = 0;
                   foreach ($row as $key => $val) {
                       $temp .="<td><b>" . $key . "</b>: " . $val . "</td>";
                       $n++;
                       if ($n == 4) {
                           $n = 0;
                           $temp .="</tr><tr>";
                       }
                   }
                   echo "<fieldset><legend>Unhandled Entries</legend><table cellspacing=2><tr>";
                   echo $temp;
                   echo "</tr></table></fieldset>";

                   $temp = "";
                   $sql = mysql_query("SELECT id, user, dbver, report, status, ts FROM $trackerdb.status WHERE quest_id=" . $quest);
                   while ($row = mysql_fetch_assoc($sql)) {
                       $temp.="<tr><td>" . $database_version[$row["dbver"]] . "</td><td><span style=color:" . $statuscolor[$row["status"]] . ">" . $status[$row["status"]] . "</span></td><td>" . nl2br($row["report"]) . "</br><i>" . date("d.m.Y H:i:s", $row["ts"]) . " by " . id2nick($row["user"]) . "</i>";
                       if (isset($_SESSION["id"]) && $row["user"] == $_SESSION["id"])
                           $temp .=" - <a href=index.php?showrev=$show_data_for_rev&filterstatus=$filter_status&quest=$quest&deletepost=" . $row["id"] . ">delete</a>";
                       $temp .="</td></tr>";
                   }
                   echo "<fieldset><legend>Quest Status</legend><table cellspacing=0 style=width:100% class=main>
	    <tr><td><b>Rev</b></td><td><b>Status</b></td><td><b>Comment</b></td></tr>";
                   echo empty($temp) ? "<tr><td colspan=3><center>No entries</center></td></tr>" : $temp;
                   if (isset($_SESSION["id"]) && $_SESSION["id"] > 0) {
                       echo "<tr><td colspan=3><b>File a new Report</b></br><form action=index.php?showrev=" . $show_data_for_rev . "&filterstatus=" . $filter_status . "&quest=$quest&doreport method=post>
	      <tt>UDB rev&nbsp;</tt><select name=rev style=width:350px><option value=-1>don't know</option>";
                       for ($i = 0; $i < count($database_version); $i++)
                           echo "<option value=$i>$database_version[$i]</option>";
                       echo "</select> Your Report won't submit if you don't know your DB rev!</br> <tt>Status&nbsp;&nbsp;</tt><select name=status style=width:350px>";
                       for ($i = 0; $i < count($status); $i++)
                           echo "<option value=$i>$status[$i]</option>";
                       echo "</select> <a href=\"javascript:alert('Unknown: default status\\n\\rnot completable: quest cannot be completed\\n\\rcore issue: bugged & cannot be solved by db, has to be done in core\\n\\rscript issue: bugged and cannot be solved by db, requires acid/sd2\\n\\rcompletable: quest can be completed\\n\\rblizzlike: quest can be completed, and EVERYTHING (spawns, npcs, texts, loot) is like on official\\n\\robsolete: not used ingame')\">what?</a></br>";
                       echo "<tt>Report&nbsp;&nbsp;</tt><textarea name=report style=width:350px;height:150px></textarea>This field is REQUIRED!</br>";
                       echo "<tt>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</tt><input type=submit style=width:350px>";
                       echo "</form>";
                   }
                   else
                       echo "<tr><td colspan=3>" . $login . "</td></tr>";
                   echo "</table>";
               }
               ?>
        </td></tr></table>
</body>
</html>
<?php
session_write_close();
