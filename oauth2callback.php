<?php
date_default_timezone_set("Europe/Berlin");
error_reporting(-1);
ini_set('display_errors', 'On');

require_once "player.php";
require_once "config.php";

$player = new player( $config );
$client = $player->get_client();

if (!isset($_GET['code'])) {

	$auth_url = $client->createAuthUrl();
	header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
}
else
{
	$client->authenticate($_GET['code']);
	$_SESSION['access_token'] = $client->getAccessToken();
	$redirect_uri = $config->urls->host . $config->urls->root;
	header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}