<?php

if ( !defined('ABSPATH') ) {
	/** Set up WordPress environment */
	require_once( dirname( __FILE__ ) . '/../wp-load.php' );
}
		
$access_token = "";

$ch = curl_init("https://graph.facebook.com/v2.3/750888968364103/feed?access_token=".$access_token."&debug=all&format=json&method=get&pretty=0&suppress_http_code=1");
curl_setopt($ch,CURLOPT_USERAGENT,'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:38.0) Gecko/20100101 Firefox/38.0');
$headers = array();
$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
$headers[] = 'Accept-Language: en-US,en;q=0.5';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
$headers[] = 'Origin: https://developers.facebook.com';
$headers[] = 'Connection: keep-alive';
	
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
$res = curl_exec($ch);


$json_dec = json_decode($res, true);
$json_data = $json_dec['data'];

foreach ($json_data as $status)
{
	$post_id=substr(strstr($status['id'], '_'), 1);
	$from_id=$status['from']['id'];
	
	$args = array(
		'meta_query' => array(
			array(
				'key'     => 'facebook_post_id',
				'value'   => $post_id,
				),
			),
	);
	$query = new WP_Query( $args );
	$post_exists=false;
	if ($query->have_posts())
	{
		$post_exists=true;
	}
	
	//check for whitelist,blacklist and graylist	
	
	$datetime = new DateTime($status['created_time']);
	$datetime->modify("+3 hours");
	
	$fbk_url = "https://www.facebook.com/RuseSignal/posts/".$post_id;
	$fbk_post = "[facebook url=\"".$fbk_url."\"]";
	
	$my_post = array(
		'post_title'    => 'Сигнал '.$datetime->format('Y-m-d') . ' ' . $datetime->format('H:i:s'),
		'post_content'  => $fbk_post,
		'post_status'   => 'publish',
		'post_author'   => 1,
		'tags_input'	=> $status['from']['name'],
//		'post_date'     => $datetime->format('Y-m-d') . ' ' . $datetime->format('H:i:s'),		
		'post_category' => array(2)
		);

	// Insert the post into the database
	if ($post_exists==false)
	{
		echo ($fbk_url."\r\n");
		$wp_post_id = wp_insert_post( $my_post );
		add_post_meta ($wp_post_id, "source", "facebook");
		add_post_meta ($wp_post_id, "facebook_post_id", $post_id);
		add_post_meta ($wp_post_id, "facebook_user_id", $from_id);
	}
	
}

?>