<?php
/* Basic script for triple store parsing.
 *
 * author   : Johann-Mattis List
 * email    : mattis.list@lingulist.de
 * created  : 2014-08-31 19:14
 * modified : 2014-08-31 19:14
 *
 */
?>
<?php 
header('Content-Type: text/plain');
$now = date('Y.m.d, H:i');
if(array_key_exists('PHP_AUTH_USER',$_SERVER)) {
  $user = $_SERVER['PHP_AUTH_USER'];
}
else {
  $user = 'unknown'; 
}
$dsn = "sqlite:triples.sqlite3";
$con = new PDO ($dsn);

function sortMyCols($valA,$valB)
{
  $sorter = array(
    "DOCULECT" => 1,
    "CONCEPT" => 2,
    "IPA" => 3,
    "TOKENS" => 4,
    "CLUSTERID" => 5,
    "ALIGNMENT" => 6
  );
  if(array_key_exists($valA, $sorter))
  {
    $valA = $sorter[$valA];
  }
  else
  {
    $valA = 10;
  }
  if(array_key_exists($valB, $sorter))
  {
    $valB = $sorter[$valB];
  }
  else
  {
    $valB = 10;
  }
  if($valA < $valB){return -1;}
  if($valB < $valA){return 1;}
  if($valA == $valB){return 0;}
}

if(isset($_GET['update']))
{
  /* get original datum */
  $query = $con->query('select VAL from '.$_GET['file'].' where ID = '.$_GET['ID'].' and COL like "'.$_GET['COL'].'";');
  $val = $query->fetch();
  
  /* insert previous datum */
  $con->exec('insert into backup(FILE,ID,COL,VAL,DATE,USER) values("'.$_GET['file'].'",'.$_GET['ID'].',"'.$_GET['COL'].'","'.$val['VAL'].'","'.$now.'","'.$user.'");');
  
  /* insert new datum */
  $con->exec('update '.$_GET['file'].' set VAL = "'.$_GET['VAL'].'" where ID = '.$_GET['ID'].' and COL like "'.$_GET['COL'].'";');

  /* give simple feedback */
  echo 'Modification successfully carried out, replaced "'.$val['VAL'].'" with "'.$_GET['VAL'].'" on '.$now.'.';
}

else if(isset($_GET['file']))
{
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
  foreach($results as $entry)
  {
    try
    {
      $data[$entry['ID']][$entry['COL']] = $entry['VAL'];
    }
    catch(Exception $e)
    {
      $data[$entry['ID']] = array($entry['COL'] => $entry['VAL']);
    }
  }

  /* iterate over array and assign all columns */
  foreach($idxs as $idx)
  {
    echo $idx;
    foreach($cols as $col)
    {
      echo "\t".$data[$idx][$col];
    }
    echo "\n";
  }
}
else
{
  echo "nofile";
}
?>


