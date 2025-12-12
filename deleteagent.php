<?php 
session_start();
require("bdburida.php");
require("admincondition.php");

$deletegerant=$bdd->prepare('DELETE FROM percepteur WHERE id_agent= :num LIMIT 1');

$deletegerant->bindvalue(':num', $_GET['suppgerant'], PDO::PARAM_INT);
$suppok=$deletegerant->execute();

if ($suppok) 
{
	echo "l'agent a été supprimé";
	header("Location:listeagent.php? id=".$_SESSION['id']);
}
else{
	echo "l'agent n'a pas été supprimé";
}

