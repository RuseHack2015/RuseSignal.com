<?php
if ( !defined('ABSPATH') ) {
	/** Set up WordPress environment */
	require_once( dirname( __FILE__ ) . '/../wp-load.php' );
}
		
ini_set('display_errors', 1);
require_once('TwitterAPIExchange.php');

/** Set access tokens here - see: https://dev.twitter.com/apps/ **/
$settings = array(
    'oauth_access_token' => "",
    'oauth_access_token_secret' => "",
    'consumer_key' => "",
    'consumer_secret' => ""
);

/** URL for REST request, see: https://dev.twitter.com/docs/api/1.1/ **/
$url = 'https://api.twitter.com/1.1/search/tweets.json';
$getfield = '?q=#rusesignal';
$requestMethod = 'GET';

/** Perform a POST request and echo the response **/
$twitter = new TwitterAPIExchange($settings);
$response = $twitter->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();

$json_dec = json_decode($response);

foreach ($json_dec->statuses as $status)
{
	$args = array(
		//'post_type' => 'post',
		'meta_query' => array(
			array(
				'key'     => 'twitter_post_id',
				'value'   => $status->id_str,
				),
			),
	);
	$query = new WP_Query( $args );
	//echo $query->request;
	$post_exists=false;
	if ($query->have_posts())
	{
		$post_exists=true;
	}
	if (array_key_exists('retweeted_status',$status))
	{
		$post_exists=true;
	}
	
	//check for whitelist,blacklist and graylist
	
	echo "https://twitter.com/".$status->user->screen_name."/status/".$status->id_str."\r\n";
	$twt_url = "https://twitter.com/".$status->user->screen_name."/status/".$status->id_str;
	
	$datetime = new DateTime($status->created_at);
	$datetime->modify("+3 hours");
	
	$my_post = array(
		'post_title'    => 'Сигнал '.$datetime->format('Y-m-d') . ' ' . $datetime->format('H:i:s'),
		'post_content'  => $twt_url,
		'post_status'   => 'publish',
		'post_author'   => 1,
//		'post_date'     => $datetime->format('Y-m-d') . ' ' . $datetime->format('H:i:s'),		
		'tags_input'	=> $status->user->screen_name,
		'post_category' => array(3)
		);

	// Insert the post into the database
	if ($post_exists==false)
	{
		$post_id = wp_insert_post( $my_post );
		add_post_meta ($post_id, "source", "twitter");
		add_post_meta ($post_id, "twitter_post_id", $status->id_str);
		add_post_meta ($post_id, "twitter_user_id", $status->user->id_str);
	}
}

?>