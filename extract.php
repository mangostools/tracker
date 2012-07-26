#!/usr/bin/env php
<?php
require("config.php");
ini_set('memory_limit', '512M');

mysql_connect($server, $user, $password, $trackerdb);
mysql_select_db($trackerdb);

/**
 * The list of DBC files to be parsed.
 *
 * @var array   $handled_dbc_files
 */
$handled_dbc_files = Array(
    "AreaTable.dbc",
    "ChrClasses.dbc",
    "ChrRaces.dbc",
    "Emotes.dbc",
    "Faction.dbc",
    "Map.dbc",
    "QuestInfo.dbc",
    "QuestSort.dbc",
    "SkillLine.dbc",
    "Spell.dbc",
);

/**
 * The strings used with PHP unpack() to process DBC files defined in $handled_dbc_files.
 * 
 * @var array   $dbc_unpack_strings
 */
$dbc_unpack_strings = Array(
    Array("string" => "L1id/Lfield1/Lfield2/L8unk/Lstring1", "fields" => 2, "strings" => 1), //AreaTable
    Array("string" => "L1id/L4unk/L1string1", "fields" => 0, "strings" => 1), //ChrClasses
    Array("string" => "L1id/L14unk/L1string1", "fields" => 0, "strings" => 1), //ChrRaces
    Array("string" => "L1id/L1string1", "fields" => 0, "strings" => 1), //Emotes
    Array("string" => "L1id/L18unk/L1string1", "fields" => 0, "strings" => 1), //Faction
    Array("string" => "L1id/Lunk/Lfield1/L1unk/Lstring1/L12unk/f2unk/Lfield2", "fields" => 2, "strings" => 1), //Map
    Array("string" => "L1id/L1string1", "fields" => 0, "strings" => 1), //QuestInfo
    Array("string" => "L1id/L1string1", "fields" => 0, "strings" => 1), //QuestSort
    Array("string" => "L1id/L2unk/L1string1", "fields" => 0, "strings" => 1), //Skilline
    Array("string" => "L1id/L119unk/Lstring1", "fields" => 0, "strings" => 1), //Spell
);

/**
 * Maps DBC fields to table names.
 * 
 * @var array   $dbc_db_info
 */
$dbc_db_info = Array(
    Array("db" => "areatable", "field1" => "map", "field2" => "parent", "string1" => "name"),
    Array("db" => "chrclasses", "string1" => "name"),
    Array("db" => "chrraces", "string1" => "name"),
    Array("db" => "emotes", "string1" => "name"),
    Array("db" => "faction", "string1" => "name"),
    Array("db" => "map", "field1" => "type", "field2" => "parent", "string1" => "name"),
    Array("db" => "questinfo", "string1" => "name"),
    Array("db" => "questsort", "string1" => "name"),
    Array("db" => "skillline", "string1" => "name"),
    Array("db" => "spell", "string1" => "name"),
);

echo "# DBC Extraction Tool for UDBView\n\n";
echo "Checking for files...\n";

$foundfiles = Array();
if ($handle = opendir('./dbc')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && in_array($file, $handled_dbc_files)) { {
                echo "Found $file\n";
                array_push($foundfiles, $file);
            }
        }
    }
    closedir($handle);
    if (count(array_diff($handled_dbc_files, $foundfiles)) > 0) {
        echo "\nCould not find the followind DBC files:\n<b>";
        echo implode("\n", array_diff($handled_dbc_files, $foundfiles));
        echo "</b>\nOperation aborted.\nCheck if the case matches!\n";
        die();
    }
} else {
    echo "Please create a directory \"dbc\" (lowercase!) and extract DBC files for \"The Game\" Version\n";
    die();
}
echo "\n";

echo "# Extracting data from DBC files\n\n";
for ($i = 0; $i < count($handled_dbc_files); $i++) {
    echo "Opening \"" . $handled_dbc_files[$i] . "\"...\n";
    $file = fopen("./dbc/" . $handled_dbc_files[$i], "rb");
    $headerdata = fread($file, 20);
    $header = unpack("a4type/Lrecords/Lfieldsperrecord/Lrecordsize/Lstringblocksize", $headerdata);
    $dataout = array();
    echo $handled_dbc_files[$i] . " has " . $header['records'] . " records, and " . $header['fieldsperrecord'] . " fields per record, each record sized " . $header['recordsize'] . " bytes. ";
    if ($header['stringblocksize'] > 0) {
        echo "A string block sized " . $header['stringblocksize'] . " bytes is appended.";
    }
    echo "\n\nReading... ";
    flush();
    $percent = 0;
    for ($j = 0; $j < $header["records"]; $j++) {
        $data = fread($file, $header["recordsize"]);
        $datau = unpack($dbc_unpack_strings[$i]["string"], $data);
        $dataout[$datau["id"]] = array();
        foreach (array_slice($dbc_db_info[$i], 1, NULL, true) as $k => $v) {
            $dataout[$datau["id"]][$k] = $datau[$k];
        }

        if (($j + 1) / $header["records"] >= $percent + 0.1) {
            $percent += 0.1;
            // echo ($percent * 100) . "%... ";
            flush();
        }
    }
    echo "\n";
    $offset_stringblock = ftell($file);
    echo "\nInserting...\n";
    flush();
    $percent = 0;
    $n = 1;
    $max = count($dataout);
    // echo nl2br(print_r($dataout,1));
    foreach ($dataout as $id => $val) {
        fseek($file, $offset_stringblock + $val["string1"]);
        $string = "";
        $doread = 1;
        while ($doread == 1) {
            $char = fread($file, 1);
            if (ord($char) == 0) {
                $doread = 0;
                $val["string1"] = mysql_real_escape_string($string);
                $fields = implode(",", array_slice($dbc_db_info[$i], 1, NULL, true));
                $values = "\"" . implode("\",\"", $val) . "\"";

                mysql_query("INSERT IGNORE INTO " . $dbc_db_info[$i]["db"] . "(id," . $fields . ") VALUES (" . $id . "," . $values . ")") or die(mysql_error());
                echo $id . " " . $string . "\n";
            }
            else
                $string.=$char;
        }
        if ($n / $max >= $percent + 0.1) {
            $percent += 0.1;
            // echo ($percent * 100) . "%... ";
            flush();
        }
        $n++;
    }
    echo "\n";
    flush();
}

echo "All done! Please check the DB Tables for any obvious errors.\n\n";