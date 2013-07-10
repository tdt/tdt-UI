<?php
 
/**
 * editting, deleting, showing the packages and resources
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

//needed for conntecting to the client
use Guzzle\Http\Client;
//needed for the PUT request
use Guzzle\Http\Message;
use Guzzle\Http\Query;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

// included for catching the 401 errors (authorization needed)
use Guzzle\Http\Exception\ClientErrorResponseException;

$app->match('/package/remove', function (Request $request) use ($app) {
	
	$client = new Client();

	try{
		$path = HOSTNAME."/tdtadmin/resources/".$request->get('path');
		$request = $client->delete($path);
		$response = $request->send();
	} catch(ClientErrorResponseException $e) {
		if ($e->getResponse()->getStatusCode() == 401) {
			
		}
	}	
	return $app->redirect('../../package');
	
});

$app->match('/resource/functions', function (Request $request) use ($jsonobj,$app) {

	// if you want to remove a resource
	if ($request->get("remove") != null){
		$client = new Client();
		try{
			$path = HOSTNAME."/tdtadmin/resources/".$request->get('path');
			$request = $client->delete($path); //->setAuth('tdtadmin','test');
			$response = $request->send();
		} catch(ClientErrorResponseException $e) {
			if ($e->getResponse()->getStatusCode() == 401) {
				
			}
			//echo $e->getMessage();
		}
		
		
		return $app->redirect('../../package');
	}
	// if you want to edit a resource
	else if($request->get("edit") != null){
		$app['session']->set('pathtoresource',$request->get('path'));
		return $app->redirect('../../resource/edit');
	}
	// if you want to get a resource in json format
	else if($request->get("json") != null){
		$client = new Client();
		try{
			$path = "http://localhost/tdt/start/public/".$request->get('path').".json";
			$request = $client->get($path);
			$response = $request->send()->getBody();
		} catch(BadResponseException $e) {
			echo $e->getMessage();
		}
		return $response;
	}
	// if you want to get a resource in php format
	else{
		$client = new Client();
		try{
			$path = "http://localhost/tdt/start/public/".$request->get('path').".php";
			$request = $client->get($path);
			$response = $request->send()->getBody();
		} catch(BadResponseException $e) {
			echo $e->getMessage();
		}
		return $response;

	}
});
