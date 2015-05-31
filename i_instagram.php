<?php

if ( !defined('ABSPATH') ) {
	/** Set up WordPress environment */
	require_once( dirname( __FILE__ ) . '/../wp-load.php' );
}
		
$ch = curl_init("https://instagram.com/explore/tags/rusesignal/?__a=1");
curl_setopt($ch,CURLOPT_USERAGENT,'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:38.0) Gecko/20100101 Firefox/38.0');
$headers = array();
$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
$headers[] = 'Accept-Language: en-US,en;q=0.5';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
$headers[] = 'Connection: keep-alive';
	
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
$res = curl_exec($ch);

$json_dec = json_decode($res);
$nodes=$json_dec->moreQuery->initial->media->nodes;

foreach($nodes as $node)
{
	$post_id = $node->code;
	$from_id = $node->owner->id;
	
	$args = array(
		'meta_query' => array(
			array(
				'key'     => 'instagram_post_id',
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

	$datetime = new DateTime();
	$datetime->setTimestamp($node->date);
	$datetime->modify("+3 hours");

	if ($post_exists==false)
	{
		$ig_url = "https://instagram.com/p/".$post_id;
		echo ($ig_url."\r\n");
		
		curl_setopt($ch,CURLOPT_URL, "https://instagram.com/p/".$post_id."/?__a=1");
		$my_res = curl_exec($ch);
		$my_json = json_decode($my_res);		

		$my_post = array(
			'post_title'    => 'Сигнал '.$datetime->format('Y-m-d') . ' ' . $datetime->format('H:i:s'),
			'post_content'  => $ig_url,
			'post_status'   => 'publish',
			'post_author'   => 1,
//			'post_date'     => $datetime->format('Y-m-d') . ' ' . $datetime->format('H:i:s'),
			'tags_input'	=> $my_json->media->owner->username,
			'post_category' => array(34)
		);
		
		$wp_post_id = wp_insert_post( $my_post );
		add_post_meta ($wp_post_id, "source", "instagram");
		add_post_meta ($wp_post_id, "instagram_post_id", $post_id);
		add_post_meta ($wp_post_id, "instagram_user_id", $from_id);
	}	
}


?>