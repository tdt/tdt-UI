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

$app->match('/ui/package/remove{url}', function (Request $request) use ($app,$hostname) {
	
	$client = new Client();

	try{
		$path = $hostname."tdtadmin/resources/".$request->get('path');
		// controlling if once in a time a username and password is given to authorise for deleting
		// if not, try without authentication
		if ($app['session']->get('userrm') == null || $app['session']->get('pswdrm') ==null) {
			$request = $client->delete($path);
		}
		else {
			$request = $client->delete($path)->setAuth($app['session']->get('userrm'),$app['session']->get('pswdrm'));
		}
		$response = $request->send();
	} catch(ClientErrorResponseException $e) {

		// the error given when authentication needed
		if ($e->getResponse()->getStatusCode() == 401) {
			// if tried with authentication and it failed 
			// or when tried without authentication and authentication is needed
			if ($e->getResponse()->getStatusCode() == 401) {
				$app['session']->set('method','remove');
				$app['session']->set('path',$path);
				$app['session']->set('redirect','../../ui/package');
				return $app->redirect('../../ui/authentication');
			}
			
		}
	}	
	return $app->redirect('../../ui/package');
	
});

$app->match('/ui/resource/functions', function (Request $request) use ($app,$hostname) {

	// if you want to remove a resource
	if ($request->get("remove") != null){
		$client = new Client();
		try{
			$path = $hostname."tdtadmin/resources/".$request->get('path');
			// controlling if once in a time a username and password is given to authorise for deleting
			// if not, try without authentication
			if ($app['session']->get('userrm') == null || $app['session']->get('pswdrm') ==null) {
				$request = $client->delete($path);
			}
			else {
				$request = $client->delete($path)->setAuth($app['session']->get('userrm'),$app['session']->get('pswdrm'));
			}
			$response = $request->send();
		} catch(ClientErrorResponseException $e) {
			// if tried with authentication and it failed 
			// or when tried without authentication and authentication is needed
			if ($e->getResponse()->getStatusCode() == 401) {
				$app['session']->set('method','remove');
				$app['session']->set('path',$path);
				$app['session']->set('redirect','../../ui/package');
				return $app->redirect('../../ui/authentication');
			}
		}
		return $app->redirect('../../ui/package');
	}
	// if you want to edit a resource
	else if($request->get("edit") != null){
		$app['session']->set('pathtoresource',$request->get('path'));
		return $app->redirect('../../ui/resource/edit');
	}
	// if you want to get a resource in json format
	else if($request->get("json") != null){
		$client = new Client();
		try{
			$path = "http://localhost/tdt/start/public/".$request->get('path').".json";
			if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
				$request = $client->get($path);
			}	
			else{
				$request = $client->get($path)->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
			}
			$response = $request->send()->getBody();
		} catch(ClientErrorResponseException $e) {
			if ($e->getResponse()->getStatusCode() == 401) {
				$app['session']->set('method','getFile');
				$app['session']->set('path',$path);
				$app['session']->set('redirect','../../ui/package');
				return $app->redirect('../../ui/authentication');
			}
		}
		return $response;
	}
	// if you want to get a resource in php format
	else{
		$client = new Client();
		try{
			$path = "http://localhost/tdt/start/public/".$request->get('path').".php";
			if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
				$request = $client->get($path);
			}	
			else{
				$request = $client->get($path)->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
			}
			$response = $request->send()->getBody();
		} catch(ClientErrorResponseException $e) {
			if ($e->getResponse()->getStatusCode() == 401) {
				$app['session']->set('method','getFile');
				$app['session']->set('path',$path);
				$app['session']->set('redirect','../../ui/package');
				return $app->redirect('../../ui/authentication');
			}
		}
		return $response;

	}
});
