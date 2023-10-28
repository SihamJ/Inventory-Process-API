<?php

/* Extraction et chargement des clés depuis l'environnement
 * Les clés seront entreposées dans un dossier 'secrets' du serveur
 */

/* Chemins */
$path_pri = $_SERVER['DOCUMENT_ROOT'] . "/secrets/pri.key";
$path_pub = $_SERVER['DOCUMENT_ROOT'] . "/secrets/pub.key";

/* Lecture des clés */
$fpri = fopen($path_pri, "r") or die("Unable to open file: " . $path_pri);
$fpub = fopen($path_pub, "r") or die("Unable to open file: " . $path_pri);

/* Extraction des clés */
$private_key = fread($fpri, filesize($path_pri));
$public_key = fread($fpub, filesize($path_pub));