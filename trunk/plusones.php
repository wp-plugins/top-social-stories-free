<?php


if(isset($_GET['url'])){
	/* proxy workaround to get google +1 data */

	
	// ------------------------------------------------------------------------------------
	// If you find some posts with a real wrong nubmer of G+ in my Top Stories
	// Plugin stats probably it's a Google+ counter bug. Sometimes google returns
	// the number of G+ of your Google Plus Page instead of the count of G+ 
	// for that post. Since the number of G+ on your page is usually really bigger
	// than the number of G+ of a post, this bring really wrong data.
	// You can fix these errors by setting the $MAXCOUNT var equal to the number of
	// G+ counter on your Google Plus page, if Google returns a number of G+ bigger
	// than this number it's lowered to 1. Better a non-data than a wrong-data.
	// ------------------------------------------------------------------------------------

	$MAXCOUNT = 20000;

	// ------------------------------------------------------------------------------------

	/*
	new method based on javascript button
	
	$q = 0;
	$contents = file_get_contents('http://plusone.google.com/_/+1/fastbutton?url='. urlencode( $_GET['url'] ) );
	preg_match( '/window\.__SSR = {c: ([\d]+)/', $contents, $matches );
	if( isset( $matches[0] ) ) $q =  (int) str_replace( 'window.__SSR = {c: ', '', $matches[0] );
	if($q>$MAXCOUNT) $q = 1;
	echo $q;
	die;
*/
	/*
	different method with curl
	*/
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $_GET['url'] . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSLVERSION,3);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,8); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 8);
	$curl_results = curl_exec ($curl);
	curl_close ($curl);
	$json = json_decode($curl_results, true);
	$q = 0;
	if(isset($json[0]['result'])) { $q = intval( $json[0]['result']['metadata']['globalCounts']['count'] ); }
	if($q>$MAXCOUNT) $q = 1;
	echo $q;
	die;
	
}




?>