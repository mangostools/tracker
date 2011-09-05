<?php
include ("config.php");
ini_set('memory_limit', '32M');

mysql_connect($server,$user,$password,$udbviewdb);
mysql_select_db($udbviewdb);




$handled_dbc_files=Array("AreaTable.dbc",
			 "Map.dbc",
			 "Spell.dbc",
			 "ChrClasses.dbc",
			 "CharTitles.dbc",
			 "ChrRaces.dbc",
			 "Faction.dbc",
			 "QuestInfo.dbc",
			 "QuestSort.dbc",
			 "SkillLine.dbc");
$dbc_unpack_string=Array(Array("string"=>"L1id/Lfield1/Lfield2/L8unk/Lstring1","fields"=>2,"strings"=>1),	//AreaTable
			 Array("string"=>"L1id/Lunk/Lfield1/L2unk/Lstring1/L53unk/Lfield2","fields"=>2,"strings"=>1),	//Map
			 Array("string"=>"L1id/L135unk/Lstring1","fields"=>0,"strings"=>1),	//Spell
			 Array("string"=>"L1id/L3unk/L1string1","fields"=>0,"strings"=>1),	//ChrClasses
			 Array("string"=>"L1id/Lunk/L1string1","fields"=>0,"strings"=>1),	//CharTitles
			 Array("string"=>"L1id/L13unk/L1string1","fields"=>0,"strings"=>1),	//ChrRaces
			 Array("string"=>"L1id/L22unk/L1string1","fields"=>0,"strings"=>1),	//Faction
			 Array("string"=>"L1id/L1string1","fields"=>0,"strings"=>1),	//QuestInfo
			 Array("string"=>"L1id/L1string1","fields"=>0,"strings"=>1),	//QuestSort
			 Array("string"=>"L1id/L2unk/L1string1","fields"=>0,"strings"=>1)	//Skilline
			);
$dbc_db_info=Array(Array("db"=>"areatable","field1"=>"map","field2"=>"parent","string1"=>"name"),
		   Array("db"=>"map","field1"=>"type","field2"=>"parent","string1"=>"name"),
		   Array("db"=>"spell","string1"=>"name"),
		   Array("db"=>"chrclasses","string1"=>"name"),
		   Array("db"=>"chartitles","string1"=>"name"),
		   Array("db"=>"chrraces","string1"=>"name"),
		   Array("db"=>"faction","string1"=>"name"),
		   Array("db"=>"questinfo","string1"=>"name"),
		   Array("db"=>"questsort","string1"=>"name"),
		   Array("db"=>"skillline","string1"=>"name")
		   );
echo "<h2>DBC Extraction Tool for UDBView</h2>";
echo "Checking for files...</br>";
$foundfiles=Array();
if ($handle = opendir('./dbc')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && in_array($file,$handled_dbc_files)) {
	  {
	  echo "Found $file</br>";
	  array_push($foundfiles,$file);
	  }
        }
    }
    closedir($handle);
if(count(array_diff($handled_dbc_files,$foundfiles))>0)
  {
  echo "</br>Could not find the followind DBC files:</br><b>";
  echo implode ("</br>",array_diff($handled_dbc_files,$foundfiles));
  echo "</b></br>Operation aborted.</br>Check if the case matches!</br>";
  die();
  }
}
else
{
echo "Please create a directory \"dbc\" (lowercase!) and extract DBC files for \"The Game\" Version corresponding to your current UDB version (e.g. 3.0.9 for UDB 380)</br>";
die();
}
echo "</br>";
for($i=0;$i<count($handled_dbc_files);$i++)
  {
  echo "Opening ".$handled_dbc_files[$i]."...</br>";
  $file=fopen("./dbc/".$handled_dbc_files[$i],"rb");
  $headerdata=fread($file,20);
  $header=unpack("a4type/Lrecords/Lfieldsperrecord/Lrecordsize/Lstringblocksize",$headerdata);
  $dataout=array();
  //echo nl2br(print_r($header,1));
  echo "Reading...";
  flush();
  $percent=0;
  for($j=0;$j<$header["records"];$j++)
    {
    $data=fread($file,$header["recordsize"]);
    $datau=unpack($dbc_unpack_string[$i]["string"],$data);
    $dataout[$datau["id"]]=array();
    foreach(array_slice($dbc_db_info[$i],1,NULL,true) as $k => $v)
    {
        $dataout[$datau["id"]][$k]=$datau[$k];
    }

    if(($j+1)/$header["records"]>=$percent+0.1)
      {
      $percent += 0.1;
      echo ($percent*100)."%...";
      flush();
      }
    }
  echo "done</br>";
  $offset_stringblock=ftell($file);
  echo "Inserting...";
  flush();
  $percent=0;
  $n=1;
  $max=count($dataout);
//   echo nl2br(print_r($dataout,1));
  foreach($dataout as $id=>$val)
    {
    fseek($file,$offset_stringblock+$val["string1"]);
    $string="";
    $doread=1;
    while($doread==1)
      {
      $char=fread($file,1);
      if(ord($char)==0)
	{
	$doread=0;
    $val["string1"]=mysql_real_escape_string($string);
    $fields=implode(",",array_slice($dbc_db_info[$i],1,NULL,true));
    $values="\"".implode("\",\"",$val)."\"";
    
	mysql_query("INSERT IGNORE INTO ".$dbc_db_info[$i]["db"]."(id,".$fields.") VALUES (".$id.",".$values.")") or die(mysql_error());
    //echo $id." ".$string."</br>";
	}
      else
	$string.=$char;
      }
    if($n/$max>=$percent+0.1)
      {
      $percent += 0.1;
      echo ($percent*100)."%...";
      flush();
      }
    $n++;
    }
  echo "done</br>";
  flush();
  }

echo "All done! Please check the DB Tables for any obvious errors"; 