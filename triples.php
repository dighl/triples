<?php
/* Basic script for handling Browsing and Modifiation of Triples
 *
 * author   : Johann-Mattis List
 * email    : mattis.list@lingulist.de
 * created  : 2014-08-31 22:09
 * modified : 2014-09-03 10:31
 *
 */
?>
<?php 
header('Content-Type: text/plain;charset=utf8');
$now = date('Y.m.d, H:i.s');
if(isset($_SERVER['REMOTE_USER'])) {
  $user = $_SERVER['REMOTE_USER'];
}
else {
  $user = 'unknown'; 
}
$dsn = "sqlite:triples.sqlite3";
$con = new PDO ($dsn);

/* this is our sorter function that handles the order of
 * columns in the data 
 */
function sortMyCols($valA,$valB) {
  $sorter = array(
    "DOCULECT" => 1,
    "CONCEPT" => 2,
    "IPA" => 3,
    "TOKENS" => 4,
    "CLUSTERID" => 5,
    "ALIGNMENT" => 6
  );
  if(array_key_exists($valA, $sorter)) {
    $valA = $sorter[$valA];
  }
  else {
    $valA = 10;
  }
  if(array_key_exists($valB, $sorter)) {
    $valB = $sorter[$valB];
  }
  else {
    $valB = 10;
  }
  if($valA < $valB) {return -1; }
  if($valB < $valA) {return 1; }
  if($valA == $valB) {return 0; }
}

/* if site is called with keyword "tables", return all tables, each in 
 * one line 
 */
if(isset($_GET['tables'])) {

  $query = $con->query('select name from sqlite_master where name != "backup";');
  $data = $query->fetchAll(PDO::FETCH_COLUMN, 0);
  foreach ($data as $table) {
    echo $table."\n";
  }
}

else if(isset($_GET['file'])) {
  
  /* get all columns */
  $sth = $con->prepare('select COL from '.$_GET['file'].';');
  $sth->execute();
  $cols = array_unique($sth->fetchAll(PDO::FETCH_COLUMN, 0));
  usort($cols, "sortMyCols");

  echo "ID";
  foreach($cols as $col)
  {
    echo "\t".$col;
  }
  echo "\n#\n";
  
  /* get all indices */
  $sth = $con->prepare('select ID from '.$_GET['file'].';');
  $sth->execute();
  $idxs = array_unique($sth->fetchAll(PDO::FETCH_COLUMN, 0));
  
  /* create text */
  $text = "";
  
  /* fetch all the data from sqlite */
  $query = 'select * from '.$_GET['file'].';';
  $sth = $con->prepare($query);
  $sth->execute();
  $data = array();
  $results = $sth->fetchAll();
  foreach ($results as $entry) {
    try {
      $data[$entry['ID']][$entry['COL']] = $entry['VAL'];
    }
    catch(Exception $e) {
      $data[$entry['ID']] = array($entry['COL'] => $entry['VAL']);
    }
  }

  /* iterate over array and assign all columns */
  foreach ($idxs as $idx) {
    echo $idx;
    foreach ($cols as $col) {
      echo "\t".$data[$idx][$col];
    }
    echo "\n";
  }
}
/* check the history of edits, if this option is chosen */
else if (isset($_GET['history'])) {
  $query = $con->query('select * from backup;');
  $data = $query->fetchAll();
  foreach ($data as $line) {
    echo $line['FILE'] . "\t" . $line["ID"] . "\t" . $line["COL"] . "\t" .
      $line["VAL"] . "\t" . $line["DATE"] . "\t" .$line["user"] . "\n";
  }
}

else
{
  echo "no parameters specified by user " . $user;
}
?>


