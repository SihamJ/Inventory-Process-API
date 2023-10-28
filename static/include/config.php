<?php

/* TODO: supprimer en production */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty(session_id())) {
    // Empêcher de faire plusieurs session_start (ce qui fait planté), si le fichier est inclus plusieurs fois
    session_start();
}
