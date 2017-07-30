<?php

class reddit {

	private $cache_file = "";
	private $cache_life = '3600'; //caching time, in seconds
	private $update_url = "https://www.reddit.com/r/oldskoolrave/new/.json?limit=25";

	function __construct()
	{
		$this->cache_file = __DIR__ . "/cache/new.json";
	}

	public function get_latest_youtube_videos()
	{

		if ( $this->needs_update() )
		{
			$videos = $this->get_updated_videos();
		}
		else
		{
			$videos = $this->get_cached_videos();
		}
		return $videos;
	}

	private function get_updated_videos() {

		$ch = curl_init();

		// set url
		curl_setopt($ch, CURLOPT_URL, $this->update_url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0");


		//return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
		curl_setopt($ch, CURLOPT_COOKIE, "loid=00000000000007ekk4.2.1333995299491.Z0FBQUFBQlpCMzZNQUtVV3lVZWNDVHNFcEo4YzFGdVhHYURhZmJBN1ptX2Z4bFBiWHJiX0JoY0xrNkdFbGZqd2h3cFVMR2YxdnNOVXZwbVA2VWtYSEF6cTZWY2lSZ0ltbmN1ZVJnTEN5a0FUeGtZaUJUVDUwamY0U1k2cVR2OC1adFBQVnA3VFNJc1c; eu_cookie_v2=3; _recent_srs=t5_2wpct%2Ct5_2s3kh%2Ct5_2rc6i%2Ct5_2sfg5%2Ct5_2t6xs%2Ct5_2qh1e%2Ct5_2s3yz%2Ct5_2w2ea%2Ct5_2uxq8%2Ct5_38aq5; aa=1; _recent_srs=t5_2sxhs%2Ct5_2wpct%2Ct5_2s3kh%2Ct5_2rc6i%2Ct5_2sfg5%2Ct5_2t6xs%2Ct5_2qh1e%2Ct5_2s3yz%2Ct5_2w2ea%2Ct5_2uxq8; dr_rentschler_recent_srs=t5_2rujb%2Ct5_2qizd%2Ct5_2yrq6%2Ct5_2vxxc%2Ct5_3hwza%2Ct5_2ti4h%2Ct5_37dym%2Ct5_2vh37%2Ct5_2sjnp%2Ct5_2u6os; edgebucket=orEfOdODucR8z2zier; reddit_session=12437140%2C2017-04-03T11%3A13%3A24%2C7b68666f9becce768fa8b924ba1ae28f446edaf6; token=eyJhY2Nlc3NUb2tlbiI6ImlOZC1jMF9XTGJBUUtjSEFXdjlGdWl5TjFuMCIsInRva2VuVHlwZSI6ImJlYXJlciIsImV4cGlyZXMiOiIyMDE3LTA3LTE3VDE5OjU3OjAwLjk1MloiLCJyZWZyZXNoVG9rZW4iOiIxMjQzNzE0MC1XcV9hakxueElXX1ZGYzNFcUhOMzZxYmtaUXciLCJzY29wZSI6ImFjY291bnQgY3JlZGRpdHMgZWRpdCBmbGFpciBoaXN0b3J5IGlkZW50aXR5IGxpdmVtYW5hZ2UgbW9kY29uZmlnIG1vZGNvbnRyaWJ1dG9ycyBtb2RmbGFpciBtb2Rsb2cgbW9kbWFpbCBtb2RvdGhlcnMgbW9kcG9zdHMgbW9kc2VsZiBtb2R3aWtpIG15c3VicmVkZGl0cyBwcml2YXRlbWVzc2FnZXMgcmVhZCByZXBvcnQgc2F2ZSBzdWJtaXQgc3Vic2NyaWJlIHZvdGUgd2lraWVkaXQgd2lraXJlYWQifQ==.2; secure_session=1; dr_rentschler_recentclicks2=t3_6ow6pb%2Ct3_6p4jn0%2Ct3_6p4pmh%2Ct3_6khs41%2Ct3_6l2ta2; session_tracker=MeVFwntcrsBtQ3y7ge.0.1500932722184.Z0FBQUFBQlpkbXB5RUUxblc2N3pKZkY0RFBOaUQxRjJkVVpKTXBkR3lYV0p6cW9WeFlHd1ZyRjBDMnJvTlE1amxDdks2Q1EzbEpiRVg1MWxfZnNzVjBrRXdfOHYzbGdpRmJYNUxYb05FU011S2lYS09oeUx2akxfVTdTanlCVDhNLTllUlU3RU4zWmc; pc=y4; initref=localhost");

		// $output contains the output string
		$output = curl_exec($ch);

		curl_close($ch);

		file_put_contents($this->cache_file, $output);

		return $this->get_videos_from_json($output);
	}

	public function needs_update()
	{
		$filemtime = @filemtime($this->cache_file);  // returns FALSE if file does not exist
		if ($filemtime && (time() - $filemtime < $this->cache_life))
		{
			return false;
		}
		return true;
	}

	private function get_videos_from_json( $json )
	{
		$object = json_decode($json);
		$posts = $object->data->children;
		$videos = array();

		if ( count($posts) > 0 )
		{
			foreach ($posts as $post) {

				preg_match("/v=(\w+)$/", $post->data->url, $matches);
				if ( sizeof($matches) == 2 )
				{
					$id = $matches[1];

					$videos[] = array(
						"id" => $id,
						"title" => $post->data->title,
						"date_reddit" => $post->data->created,
						"id_reddit" => $post->data->id
					);
				}
			}
		}
		return $videos;
	}

	private function get_cached_videos()
	{
		$json = file_get_contents($this->cache_file);
		if ( $json )
		{
			return $this->get_videos_from_json( $json );
		}
	}
}



