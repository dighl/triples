<?php
/* Basic script for handling Browsing and Modifiation of Triples
 *
 * author   : Johann-Mattis List
 * email    : mattis.list@lingulist.de
 * created  : 2014-08-31 22:09
 * modified : 2014-09-04 20:29
 *
 */
?>
<?php 
header('Content-Type: text/plain;charset=utf8');
$now = date('Y-m-d H:i:s');
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
if(isset($_GET['update'])) {
  
  /* check if column exists first */
  $query = $con->query('select DISTINCT COL from '.$_GET['file'] .';');
  $cols = $query->fetchAll();

  if (in_array($_GET['COL'], $cols)) {
    /* get original datum */
    $query = $con->query(
      'select VAL from '.$_GET['file'].' where ID = '.$_GET['ID'].' and COL like "' . 
      $_GET['COL'].'";'
    );

    $val = $query->fetch();
    
    /* insert previous datum */
    $con->exec(
      'insert into backup(FILE,ID,COL,VAL,DATE,USER) values("'.$_GET['file'] .
      '",'.$_GET['ID'].',"'.$_GET['COL'].'","'.$val['VAL'].'","'.$now.'","'.$user .
      '");'
    );
    
    /* insert new datum */
    $con->exec(
      'update '.$_GET['file'].' set VAL = "'.$_GET['VAL'].'" where ID = '.$_GET['ID'] . 
      ' and COL like "'.$_GET['COL'].'";'
    );

    /* give simple feedback */
    echo 'Modification successfully carried out, replaced "'.$val['VAL'].'" with "' . 
      $_GET['VAL'].'" on '.$now.'.';
  }
  else {

    /* create new value */
    $con->exec(
      'insert into '.$_GET['file'].' values(' . 
      $_GET['ID'] . ',"' . 
      $_GET['COL'].'","' . 
      $_GET['VAL'].'");'
    );

    /* return check signal */
    echo 'Successfully inserted new values in new column on ' . $now.'.';
  }

}
