<?php


date_default_timezone_set("Europe/Berlin");
error_reporting(-1);
ini_set('display_errors', preg_match('/localhost/', $_SERVER["SERVER_NAME"]) ? 'On' : 'Off');

header('Content-Type: application/json');

if ( !isset($_GET) ) exit(json_encode(array("error" => "get not set")));

define('DEBUG', false);

require("../config.php");
require("../reddit.php");
require("../player.php");

$response = array();

$player = new player( $config );

$genres = isset($_GET["genres"]) ? $_GET["genres"] : null;
$years = isset($_GET["years"]) ? $_GET["years"] : null;
$page = isset($_GET["page"]) ? $_GET["page"] : null;

if ( isset($_GET["updated"]) )
{
	$reddit = new reddit();
	$videos_latest = $reddit->get_latest_youtube_videos();
	$player->add_videos_to_csv( $videos_latest );

	$response["updated"] = true;
}

if ( $genres === "" )
{
	$response["genres"] = $player->get_genres();
}

if ( $years === "" )
{
	$response["years"] = $player->get_years();
}

if ( isset($_GET["videos"]) )
{
	$response["videos"] = $player->get_videos(
		$years != "" ? explode(", ", $years) : null,
		$genres != "" ? explode(", ", $genres) : null,
		$page != "" ? $page : null
	);
}

if ( isset($_GET["playlist_html"]) )
{
	$response["playlist_html"] = $player->get_playlist_html(
		$years != "" ? explode(", ", $years) : null,
		$genres != "" ? explode(", ", $genres) : null,
		$page != "" ? $page : null
	);
}

echo json_encode($response);