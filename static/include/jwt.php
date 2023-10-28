<?php

require_once 'include/secrets.php';

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Firebase\JWT\JWT; /* REQUIS */
use Firebase\JWT\Key;

/* Gestion des JWT basée sur Firebase */

JWT::$leeway = 60;

global $public_key;
$public_key_jwt = new Key($public_key, 'RS256');


/* Crée un JWT
 * @param username Nom d'utilisateur du récepteur.
 * @param uid ID de l'utilisateur.
 * @param privileges Niveau de privilèges du récepteur.
 * @param seconds Durée de vie du jeton (4 heures par défaut).
 *
 * @return Un JWT sous forme d'une chaîne de caractères.
 */
function create_token($username, $uid, $privileges, $seconds = 60 * 60 * 4): string {
	global $private_key;

	// Création des estampilles
	$iat = time();
	$exp = $iat + $seconds;

	$token_payload = [
	'iss' => 'mamazon.zefresk.com',
	'sub' => $username,
	'uid' => $uid,
	'aud' => $privileges,

	'iat' => $iat,
	'exp' => $exp, // 12h
	'jti' => rtrim(base64_encode(random_bytes(8)), "=")
	];
	return JWT::encode($token_payload, $private_key, 'RS256');
}


/* Parse un JWT et émet une exception s'il est invalide
 * @param raw_token JWT sous forme de chaîne de caractère
 *
 * @return Le payload du JWT sous forme d'un dictionnaire
 *
 * Si le jeton est invalide, émet une exception.
 */
function parse_token($raw_token): array{
	global $public_key_jwt;

	return (array)JWT::decode($raw_token, $public_key_jwt);
}
