<?php
// Copyright (C) 2012 Aftonbladet, all rights reserved

function isset_or(&$check, $alternate = NULL) { 
    return (isset($check) && !empty($check)) ? $check : $alternate; 
}

/**
 * Get number of tweets about a post
 * @param integer $post_id Post id
 */
function get_tweets($url) {
	$json_string = file_get_contents('http://urls.api.twitter.com/1/urls/count.json?url=' . $url);
	$json = json_decode($json_string, true);
	$count = isset_or($json['count'], 0);
	return intval($count);
}

/**
 * Get number of likes for a post
 * @param integer $post_id Post id
 */
function get_likes($url) {
	$json_string = file_get_contents('http://graph.facebook.com/?ids=' . $url);
	$json = json_decode($json_string, true);
	//Get value
	$count = isset_or($json[urldecode($url)]['shares'], 0);
	return intval($count);
}

/**
 * Get number of +1 for a post
 * @param integer $post_id Post id
 */
function get_plusones($url) {
	//Fetch fresh value
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	$curl_results = curl_exec ($curl);
	curl_close ($curl);
	$json = json_decode($curl_results, true);
	//Get value
	$count = isset_or($json[0]['result']['metadata']['globalCounts']['count'], 0);
	return intval($count);
}

$url = $_GET['rss'];
if(empty($url)) {
	echo "The parameter rss is missing.";
	die();
}
$doc = new DOMDocument();
$doc->load($url);
$feed = array();
foreach ($doc->getElementsByTagName('item') as $node) {
	$itemRSS = array (
      'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
      'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
      'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
      'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue
	);
	array_push($feed, $itemRSS);
}
echo '
<html>
<head>
<title>'.$url.'</title>
</head>
<body>
<table border="0">
<tr>
<td>Title</td>
<td>Date</td>
<td>Facebook</td>
<td>Twitter</td>
<td>Google+</td>
<td>Sum</td>
</tr>
';
foreach($feed as $index => $item) {
	$tweets = get_tweets($item['link']);
	$likes = get_likes($item['link']);
	$plusones = get_plusones($item['link']);
	$sum = $tweets + $likes + $plusones;
	echo '
	<tr>
	<td><a href="'.$item['link'].'" target="_blank">'.htmlspecialchars($item['title']).'</a></td>
	<td>'.$item['date'].'</td>
	<td>'.$likes.'</td>
	<td>'.$tweets.'</td>
	<td>'.$plusones.'</td>
	<td>'.$sum.'</td>
	</tr>
	';
}
echo '
</table>
</body>
</html>
';
die();
