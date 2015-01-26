<?php
/* Basic script for handling Browsing and Modifiation of Triples
 *
 * author   : Johann-Mattis List
 * email    : mattis.list@lingulist.de
 * created  : 2014-08-31 22:09
 * modified : 2015-01-14 06:17
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

if (isset($_GET['remote_dbase'])) {
  $dsn = "sqlite:".$_GET['remote_dbase'];
}
else {
  $dsn = "sqlite:triples.sqlite3";
}  
$con = new PDO ($dsn);

/* if site is called with keyword "tables", return all tables, each in 
 * one line 
 */
if(isset($_GET['update'])) {
  
  include('rechte.php');

  if (in_array($_GET['file'], $rights[$user])) {

    /* get original datum */
    $query = $con->query(
      'select VAL from '.$_GET['file'].' where ID = '.$_GET['ID'].' and COL like "' . 
      $_GET['COL'].'";'
    );

    /* check if datum exists */
    //if ($query) {

    $val = $query->fetch();
    if($val != ''){
      
      /* insert previous datum */
      $con->exec(
        'insert into backup(FILE,ID,COL,VAL,DATE,USER) values("'.$_GET['file'] .
        '",'.$_GET['ID'].',"'.$_GET['COL'].'","'.$val['VAL'].'",strftime("%s","now"),"'.$user .
        '");'
      );
      
      /* insert new datum */
      $con->exec(
        'update '.$_GET['file'].' set VAL = "'.$_GET['VAL'].'" where ID = '.$_GET['ID'] . 
        ' and COL like "'.$_GET['COL'].'";'
      );

      /* give simple feedback */
      echo 'UPDATE: Modification successfully carried out, replaced "'.$val['VAL'].'" with "' . 
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
      echo 'INSERTION: Successfully inserted new values in new column on ' . $now.'.';
    }
  }
  else {
    echo 'ERROR: Current user does not have the permission to modify the data. ';
  }
}
else if (isset($_POST['column'])) {
  
  if ($user == "Mattis" || $user == "unknown") {
    /* get all ids first */
    $query = $con->query('select DISTINCT ID from '.$_POST['file'].';');
    $idxs = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($idxs as $idx) {
      if (isset($_POST[$idx])) {
        $con->exec('insert into '.$_POST['file'].' values(' . 
          $idx . ',"' . 
          $_POST['column'].'","' . 
          $_POST[$idx].'");'
        );
      }
    }
    echo 'COLUMN was successfully inserted on '. $now. '.';
  }
  else {
    echo "Error in modification attempt.";
  }
}
/* delete all entries for a given id from the database if this option is submitted by
 * providing the keyword "delete" in the get-line */
else if (isset($_GET['delete'])) {
  
  include('rechte.php');
  
  if (in_array($_GET['file'], $rights[$user])) {
    /* get all values of the id first */
    $query = $con->query('select * from '.$_GET['file'].' where ID = '.$_GET['ID'].'.;');
    $items = $query->fetchAll();
    foreach($items as $item) {
      /* insert previous datum */
      $con->exec(
        'insert into backup(FILE,ID,COL,VAL,DATE,USER) values("'.$_GET['file'] .
        '",'.$_GET['ID'].',"'.$item['COL'].'","'.$item['VAL'].'",strftime("%s","now"),"'.$user .
        '");'
      );
    }
    $con->query('delete from '.$_GET['file'].' where ID = '.$_GET['ID'].';');
    echo 'Successfully deleted the required entries from the database.';
  }
  else {
    echo 'ERROR: Current user does not have the permission to modify the data. ';
  }
}
