<?php
/* Basic script for handling Browsing and Modifiation of Triples
 *
 * author   : Johann-Mattis List
 * email    : mattis.list@lingulist.de
 * created  : 2014-08-31 22:09
 * modified : 2014-09-07 17:33
 *
 */
?>
<?php
header('HTTP/1.1 200 OK');
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="triples.tsv"');
$now = date('Y-m-d H:i:s');
if(isset($_SERVER['REMOTE_USER'])) {
  $user = $_SERVER['REMOTE_USER'];
}
else {
  $user = 'unknown'; 
}

if (isset($_GET['remote_dbase'])) {
  $dsn = "sqlite:".$_GET['remote_dbase'];
}
else {
  $dsn = "sqlite:triples.sqlite3";
}
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
/* return most recent edits, if this is chosen */
else if (isset($_GET['date'])) {
  $query = $con->query('select ID,COL from backup where FILE="' .
    $_GET['file'] . '" and datetime(DATE) > datetime("' . $_GET['date'] .
    '") group by ID,COL;');
  $data = $query->fetchAll();
  foreach ($data as $line) {
    $query = $con->query('select VAL from ' . $_GET['file'] . ' where ID=' .
      $line['ID'] . ' and COL like "' . $line['COL'] .'";');
    $tmp = $query->fetch();
    echo $line['ID'] . "\t" . $line["COL"] . "\t" . $tmp["VAL"] . "\n";
  }
}
else if (isset($_GET['new_id'])) {
  /* get all indices */
  /* start with the case that an explicit ID is chosen */
  if ($_GET['new_id'] == '') {
    $sth = $con->prepare('select DISTINCT ID from ' . $_GET['file'] . ';');
    $sth->execute();
    $idxA = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
  
    /* do the same for backup file */
    $sth = $con->prepare('select DISTINCT ID from backup where file == "'. $_GET['file']. '";');
    $sth->execute();
    $idxB = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

    $maxA = max($idxA);
    $maxB = max($idxB);
    if ($maxA >= $maxB) {
      echo $maxA + 1;
    }
    else {
      echo $maxB + 1;
    }
  }
  /* yet another possibility is that the new id is supposed to be a cognate id or the like */
  else {
    $sth = $con->prepare('select DISTINCT VAL from '.$_GET['file'] .' where COL == "'.$_GET['new_id'].'" order by COL;');
    $sth->execute();
    $idxs = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
    for ($i=0;$i<=count($idxs)+1;$i++) {
      if (!in_array($i, $idxs)) {
	echo $i;
	break;
      }
    }
  } 
}

else if(isset($_GET['file'])) {
  
  /* we make some simple solution here: if columns are passed from the 
   * get-line, we modify the query, if not, we represent the modification
   * as an empty string */
  if (!isset($_GET['columns'])) {
    $where = '';
  }
  else {
    $cols = explode(",",$_GET['columns']);
    $col_string = '("'.implode('","',$cols).'")';
    $where = ' where COL in '.$col_string;
  }

  /* set up array for ids we want to have included */
  $sth = $con->prepare('select DISTINCT ID from '.$_GET['file'].';');
  $sth->execute();
  $aids = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
  $array_count = count($aids);
  $dids = $aids;
  $cids = $aids;
  $idxs = array();


  /* similarly, we do the same for doculects and concepts, but we do not
   * combine the queries but rather use them distinctely to narrow down
   * the number of possible ids to be sent off */
  
  /* if doculects are passed, we need to make a pre-selection of possible ids */
  if (isset($_GET['doculects'])) {
    $doc_string = '("'.implode('","',explode(',',$_GET['doculects'])).'")';

    $sth = $con->prepare('select DISTINCT ID from ' . $_GET['file'] .
      ' where VAL in '.$doc_string. ';');
    $sth->execute();
    $dids = $sth->fetchAll(PDO::FETCH_COLUMN,0);
  }

  /* if concepts are passed, we need to further sort by concept selection */
  if (isset($_GET['concepts'])) {
    $con_string = '("'.implode('","',explode(',',$_GET['concepts'])).'")';

    $sth = $con->prepare('select DISTINCT ID from ' . $_GET['file'] .
      ' where VAL in '.$con_string.';');
    $sth->execute();
    $cids = $sth->fetchAll(PDO::FETCH_COLUMN,0);
  }

  $idxs = array_intersect($dids, $cids);
  
  /* get all columns */
  $sth = $con->prepare('select DISTINCT COL from ' . $_GET['file'] . $where. ';');
  $sth->execute();
  $cols = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
  usort($cols, "sortMyCols");

  echo "ID";
  foreach($cols as $col)
  {
    echo "\t".$col;
  }
  echo "\n#\n";

  /* create text */
  $text = "";

  /* $data stores the data in json-like form */
  $data = array();

  $qstring = 'select * from '.$_GET['file'].$where.' and VAL!="-" and VAL!="" and ID in ('.implode(",",$idxs).');';
  $query = $con->query($qstring);
  $results = $query->fetchAll();
  foreach ($results as $entry) {
    try {
      $data[$entry['ID']][$entry['COL']] = $entry['VAL'];
    }
    catch (Exception $e) {
      $data[$entry['ID']] = array($entry['COL'] => $entry['VAL']);
    }
  }

  /* iterate over array and assign all columns */
  foreach ($idxs as $idx) {
    echo $idx;
    foreach ($cols as $col) {
      if (isset($data[$idx][$col])) {
        echo "\t" . $data[$idx][$col];
      }
      else {
        echo "\t";
      }
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
