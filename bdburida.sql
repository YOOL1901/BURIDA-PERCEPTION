<?php
session_start();
$_SESSION['moment'] = time();
try
{
    $bdd = new PDO('mysql:host=;dbname=;charset=utf8', '', '');
}
catch (Exception $e)
{
        die('Erreur :'.$e->getMessage());
}
