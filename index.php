<!DOCTYPE html>
<html>
<head>
	<title>OLD SKOOL RAVE PLAYER</title>
</head>
<body>
<h1>OLD SKOOL RAVE PLAYER</h1>

<p>alpha version</p>

<form method="POST">
<?php

date_default_timezone_set("Europe/Berlin");
error_reporting(-1);
ini_set('display_errors', preg_match("localhost", $_SERVER["SERVER_NAME"]) ? 'On' : 'Off');

require("config.php");
require("reddit.php");
require("player.php");


$reddit = new reddit();
$videos_latest = $reddit->get_latest_youtube_videos();

$player = new player( $config );
$player->add_videos_to_csv( $videos_latest );
// $player->add_videos_to_youtube( $videos_latest );

$videos_all = $player->get_videos();

// get available years + genres
$genres = $player->get_genres();
$years = $player->get_years();

// get min max year
$year_min = count($years) > 0 ? $years[0] : null;
$year_max = count($years) > 0 ? $years[count($years) - 1] : null;



// received post data? prepare data for filter request
$set_page = isset($_POST["page"]) ? $_POST["page"] : 0;
$set_year_from = isset($_POST["year-from"]) ? $_POST["year-from"] : $year_min;
$set_year_to = isset($_POST["year-to"]) ? $_POST["year-to"] : $year_max;
$set_genres = isset($_POST["genre"]) ? $_POST["genre"] : array("all");
$set_years = array();
if ( $year_min && $year_max )
for ($i=$set_year_from; $i <= $set_year_to; $i++)
{
	array_push($set_years, $i);
}

console($set_years);


// get filtered playlist html
$playlist_html = $player->get_playlist_html( $set_years, $set_genres, $set_page );

if ( $playlist_html )
{
	echo $playlist_html;
	echo "\n";
	echo $player->get_pagination_html();
}
else
{
	echo "<h3>no matches found</h3>";
}
?>

<div id="comments" style="display: none"><a href="" class="reddit"></a> of current video</div>




<fieldset>
<legend>year</legend>
<div style="display: inline-block">
	<select name="year-from" id="year-from">
	<?php
	if ( $year_min && $year_max )
	for ($i=$year_min; $i <= $year_max; $i++)
	{
		echo "<option value='$i' " . ($i == $set_year_from ? "selected='selected'" : "") .">$i</option>";
	}
	?>
	</select>
</div>
<div style="display: inline-block"> - </div>
<div style="display: inline-block">
	<select name="year-to" id="year-to">
	<?php
	if ( $year_min && $year_max )
	for ($i=$year_min; $i <= $year_max; $i++)
	{
		echo "<option value='$i' " . ($i == $set_year_to ? "selected='selected'" : "") .">$i</option>";
	}
	?>
	</select>
</div>

</fieldset>

<fieldset>
<legend>tags</legend>
	<input type="checkbox" id="all" checked><label for="all">all!!</label>
<?php
foreach ($genres as $genre => $number )
{
	if ( $set_genres == array("all") || in_array($genre, $set_genres) )
	{
		$checked = "checked";
	}
	else
	{
		$checked = "";
	}
?>
	<input type="checkbox" name="genre[]" id="<?= $genre ?>" value="<?= $genre ?>" <?= $checked ?>><label for="<?= $genre ?>"><?= "$genre ($number)" ?></label>
<?php
}
?>
</fieldset>

<input type="submit" value="submit">
</form>



<script type="text/javascript" src="application.js"></script>
<script type="text/javascript">

	var all = document.getElementById("all");
	if ( all ) {
		all.addEventListener("change", function checkAll(e) {
			if ( e.target.checked ) {
				Array.prototype.map.call((document.querySelectorAll('[name^="genre"]') || []), function(el){el.setAttribute('checked', 'checked')});
			} else {
				Array.prototype.map.call((document.querySelectorAll('[name^="genre"]') || []), function(el){el.removeAttribute('checked')});
			}
		})
	}
</script>
<script type="text/javascript" src="https://www.youtube.com/iframe_api"></script>
<script type="text/javascript">

	var videos = <?= json_encode($videos_all); ?>;
	console.log("videos", videos);
	var a = document.querySelector("a.reddit");
	var c = document.querySelector("#comments");
	function onYouTubeIframeAPIReady (pid) {
		new YT.Player(document.querySelector("iframe"), {
			events: {
				onStateChange: function (state) {
					if ( state.data === 1 ) {
						var videoData = state.target.getVideoData();
						if ( videoData && Array.isArray(videos) ) {
							videos.map(function (video) {
								console.log('test', video.id , videoData.video_id);
								if ( video.id == videoData.video_id && a ) {
									console.log("palying: ", video);
									c.style.display = "";
									a.setAttribute("href", "https://reddit.com/r/oldskoolrave/"+video.id_reddit);
									a.innerHTML = "reddit comments";
								}
							});
						}
					}
				}
			}
		});
	}
</script>
</body>
</html>