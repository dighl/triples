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

/* if site is called with keyword "tables", return all tables, each in 
 * one line 
 */
if(isset($_GET['update'])) {
  
  /* check if column exists first */
  $query = $con->query('select DISTINCT COL from '.$_GET['file'] .';');
  $cols = $query->fetchAll(PDO::FETCH_COLUMN, 0);

  if (in_array($_GET['COL'],$cols)) {
    /* get original datum */
    $query = $con->query(
      'select VAL from '.$_GET['file'].' where ID = '.$_GET['ID'].' and COL like "' . 
      $_GET['COL'].'";'
    );

    $val = $query->fetch();
    
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
    echo 'Modification successfully carried out, replaced "'.$val['VAL'].'" with "' . 
      $_GET['VAL'].'" on '.$now.'.';
  }
  else {
    /* we store innovations also in our backup file, but we need to make sure 
     * that upon updating the respective column actually exists */
    $con->exec(
      'insert into backup(FILE,ID,COL,VAL,DATE,USER) values("'.$_GET['file'] .
      '",'.$_GET['ID'].',"'.$_GET['COL'].'","'.$_GET['VAL'].'",strftime("%s","now"),"'.$user .
      '");'
    );

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
