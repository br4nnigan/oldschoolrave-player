<?php
require_once 'google-api-php-client-2.2.0_PHP54/vendor/autoload.php';
require_once "func.php";

class player {

	private $config = array();

	private $_service = null;
	private $_client = null;

	private $_videos = null;
	private $_page_size= 100;
	private $_page_current = -1;
	private $_pages_total = -1;
	private $_videos_total = -1;

	private $csv_file = "cache/playlistitems.csv";

	function __construct( $config )
	{
		session_start();
		$this->_config = $config;
	}

	public function get_client()
	{
		if ( $this->_client == null )
		{
			$client = new Google_Client();
			$client->addScope("https://www.googleapis.com/auth/youtube");
			$client->setAuthConfig($this->_config->youtube->json);
			$client->setAccessType('offline');

			if ( isset($_SESSION['access_token']) )
			{
				$client->setAccessToken($_SESSION['access_token']);
			}
			else if ( $_SERVER["REQUEST_URI"] != $this->_config->urls->oauthcallback )
			{
				// echo  $_SERVER["REQUEST_URI"] . " is not ". $this->auth_url;
				$redirect_uri = 'http://' . $_SERVER["HTTP_HOST"] . $this->_config->urls->oauthcallback;
				header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
			}



			// $client->setIncludeGrantedScopes(true);

			// $client->setDeveloperKey($this->api_key);
			// $client->setApiKey($this->api_key);


			// $client->setScopes("https://www.googleapis.com/auth/youtube");
			// $client->setRedirectUri('http://localhost/oldschoolrave-player/redirect.php');

			$this->_client = $client;
		}

		return $this->_client;
	}

	public function get_service()
	{
		$client = $this->get_client();

		if ( $this->_service == null )
		{
			$this->_service = new Google_Service_YouTube($client);
		}

		return $this->_service;
	}


	public function add_videos_to_youtube( $videos )
	{

		// echo "add videos: <br>";
		// print_r($videos);

		$existing_videos = $this->get_videos();
		// $service = $this->get_service();

		// echo "got existing_videos videos: <br>";
		// print_r($existing_videos);

		$videos = array_filter($videos, $this->_videoExists);
		$videos = array_values($videos);

		// echo "remaining playlist videos: <br>";
		// print_r($videos);

		foreach ($videos as $video)
		{
			$service = $this->get_service();
			$resourceId = new Google_Service_YouTube_ResourceId();
			$resourceId->setKind('youtube#video');
			$resourceId->setVideoId($video["id"]);

			$snippet = new Google_Service_YouTube_PlaylistItemSnippet();
			$snippet->setPlaylistId($this->_config->youtube->playlist_id);
			$snippet->setResourceId($resourceId);

			$playlistItem = new Google_Service_YouTube_PlaylistItem();
			$playlistItem->setSnippet($snippet);

			$playlistItems = $service->playlistItems->insert(
				'snippet',
				$playlistItem
			);
		}
	}

	private function _videoExists($video, $existing_videos=null)
	{
		if ( $existing_videos == null )
		{
			$existing_videos = $this->get_videos();
		}
		foreach ($existing_videos as $existing_video)
		{
			if ( $existing_video["id"] == $video["id"] )
			{
				return true;
			}
		}
		return false;
	}

	public function add_videos_to_csv( $videos )
	{
		$existing_videos = $this->get_videos();

		console("existing videos: ", $existing_videos, "new: ", $videos);

		$videos_to_add = 0;
		for ($i=0; $i < count($videos); $i++)
		{
			$video = $videos[$i];

			if ( !$this->_videoExists($video, $existing_videos) )
			{
				console("echo adding ", $video["id"]);

				$videos_to_add++;
				array_unshift($existing_videos, $video);
			}
		}

		// sort by date
		function compare($a, $b){
			if ($a["date_reddit"] == $b["date_reddit"]) {
				return 0;
			}
			$c = ((Float) $a["date_reddit"] > (Float) $b["date_reddit"]);
			return $c ? -1 : 1;
		}
		uasort($existing_videos, "compare");
		$existing_videos = array_values($existing_videos);

		if ( $videos_to_add > 0 )
		{
			$csv_array = array();
			for ($i=0; $i < count($existing_videos); $i++) {
				$video = $existing_videos[$i];
				array_push($csv_array, array(
					 $video["id"], $video["title"], $video["id_reddit"], $video["date_reddit"]
				));
			}
			$csv = str_putcsv_array($csv_array);

			file_put_contents($this->csv_file, $csv);
		}
		else
		{
			// echo "nothgin changed ";
		}

	}

	public function get_videos( $years = null, $genres = null )
	{
		// console("get_videos", $years, $genres);

		if ( $this->_videos == null )
		{
			$this->_videos = array();

			$file = file_get_contents($this->csv_file);

			if ( $file != "" )
			{
				$this->_videos = str_getcsv_array($file);

				for ($i=0; $i < count($this->_videos); $i++)
				{
					$video = $this->_videos[$i];

					$this->_videos[$i] = array(
						"id" => $video[0],
						"title" => $video[1],
						"id_reddit" => $video[2],
						"date_reddit" => $video[3]
					);

					$video_genre = $this->get_genre( $this->_videos[$i] );
					$video_year = $this->get_year( $this->_videos[$i] );

					$this->_videos[$i]["year"] = $video_year;
					$this->_videos[$i]["genre"] = $video_genre;
				}
			}
		}

		if ( $years != null || $genres != null )
		{
			return $this->_filterVideos( $years, $genres );
		}
		return $this->_videos;
	}

	private function _filterVideos( $years = null, $genres = null )
	{
		$videos = $this->get_videos();

		for ($i=0; $i < count($videos); $i++)
		{
			if ( $years != null && $videos[$i]["year"] != null && !in_array($videos[$i]["year"], $years) )
			{
				// console("removed video , year: ", $videos[$i]["year"], $videos[$i]);
				$videos[$i] = null;

			}
			if ( $genres != null && $videos[$i]["genre"] != null && $genres != array("all") && !array_intersect($videos[$i]["genre"], $genres))
			{
				// console("removed video , video_genre: ", $videos[$i]["genre"], $videos[$i]);
				$videos[$i] = null;
			}
			if ( $videos[$i] != null )
			{
				// console("kept video: ",$videos[$i]);
			}
		}
		$videos = array_filter($videos);
		$videos = array_values($videos);

		return $videos;
	}

	private function get_genre($video)
	{
		global $matches;

		$genre = array("unknown");

		preg_match("/\[.*?([^0-9\]\[]+).*\]/", $video["title"], $matches);
		if ( count($matches) < 2 )
		{
			preg_match("/\(.*?([^0-9\(\)]+).*\)/", $video["title"], $matches);
		}

		if ( count($matches) >= 2 )
		{
			if ( strpos($matches[1], ",") !== false )
			{
				$genre = explode(",", $matches[1]);
			}
			else if ( strpos($matches[1], "/") !== false )
			{
				$genre = explode("/", $matches[1]);
			}
			else
			{
				$genre = array($matches[1]);
			}

			array_walk($genre, function(&$value){
				global $matches;
				$value = trim($value);
				$value = strtolower($value);
				if ( strpos(strtolower($matches[1]),"gabb") != false && $value == "hardcore" )
				{
					$value = null;
				}
			});
			$genre = array_filter($genre);
		}
		return $genre;
	}


	public function get_genres()
	{
		$genres = array();
		$videos = $this->get_videos();
		foreach ($videos as $video)
		{
			$genre = $this->get_genre( $video );

			if ( $genre )
			{
				foreach ($genre as $value)
				{
					if ( !isset($genres[$value]) )
					{
						$genres[$value] = 1;
					}
					else
					{
						$genres[$value]++;
					}
				}
			}
		}
		return $genres;
	}

	private function get_year($video)
	{
		preg_match("/\b(19d{2,2})\b/", $video["title"], $year0);
		if ( isset($year0[1]) )
		{
			return $year0[1];
		}

		preg_match("/.*-.*-\s*(\d{2,4})/", $video["title"], $year2);
		if ( isset($year2[1]) )
		{
			return $year2[1];
		}

		preg_match("/(\d+)\s*\[.+\]/", $video["title"], $year1);
		if ( isset($year1[1]) )
		{
			return $year1[1];
		}
	}

	public function get_years()
	{
		$years = array();
		$videos = $this->get_videos();
		foreach ($videos as $video)
		{
			$year = $this->get_year( $video );

			if ( $year && !in_array($year, $years) )
			{
				array_push($years, $year);
			}
		}
		sort($years);
		return $years;
	}

	public function get_playlist_html( $years = null, $genres = null, $page = 0 )
	{
		return $this->_get_playlist_html_generated( $years, $genres, $page );
	}

	private function _get_playlist_html_youtube()
	{
return <<<HTML
		<iframe id='player' width='640' height='360' src='http://www.youtube.com/embed?playlist=" . $this->youtube->playlist_id. "&enablejsapi=1' frameborder='0' allowfullscreen></iframe>
HTML>
	}

	private function _get_playlist_html_generated( $years, $genres, $page )
	{
		$videos = $this->get_videos( $years, $genres );
		$size = sizeof($videos);

		if ( $size == 0 )
		{
			return "";
		}

		$this->_page_current = $page;
		$this->_pages_total = ceil($size / $this->_page_size);
		$this->_videos_total = $size;

		$ids = array();
		for ($i=$page * $this->_page_size; $i < sizeof($videos); $i++)
		{
			array_push($ids, $videos[$i]["id"]);

			if ( count($ids) == $this->_page_size ) break;
		}
		$ids  = implode(",", $ids);
return <<<HTML
		<iframe id='player' width='640' data-page='$page' data-size='$size' height='360' src='http://www.youtube.com/embed?playlist=$ids&enablejsapi=1' frameborder='0' allowfullscreen></iframe>"
HTML;
	}

	public function get_pagination_html()
	{
		$html = "";

		if ( $this->_page_current == -1 || $this->_pages_total < 2 || $this->_videos_total == -1 ) return "";

		$html .=
<<<HTML
<fieldset>
<legend>page</legend>
HTML;
		for ($i=0; $i < $this->_pages_total; $i++) {

			$from = ($i * $this->_page_size) + 1;
			$to = $from + $this->_page_size - 1;
			if ( $i == $this->_pages_total - 1 )
			{
				$to -= $this->_pages_total % $this->_page_size;
			}

			if ( $this->_page_current == $i )
			{
				$html .=
<<<HTML
					<button disabled>$from - $to</button>
HTML;
			}
			else
			{
				$html .=
<<<HTML
					<button name="page" value="$i" type="submit">$from - $to</button>
HTML;
			}

		}

		$html .=
<<<HTML
</fieldset>
HTML;

		return $html;
	}
}


