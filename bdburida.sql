<?php
session_start();
$_SESSION['moment'] = time();
try
{
    $bdd = new PDO('mysql:host=185.98.131.214;dbname=tabit2577523_1h14da;charset=utf8', 'tabit2577523', 'nopcxdesqb');
}
catch (Exception $e)
{
        die('Erreur :'.$e->getMessage());
}
