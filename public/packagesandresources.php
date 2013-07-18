<?php
 
/**
 * Getting the packages and resources
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Needed for conntecting to the client
use Guzzle\Http\Client;
// included for catching the 401 errors (authorization needed)
use Guzzle\Http\Exception\ClientErrorResponseException;

// Representing the data in twig.
$app->get('/ui/package{url}', function () use ($app,$hostname,$data) {
	// Create a client (to get the data)
	$client = new Client($hostname);

	// getting the packages in json format
	try {
		// checking if once in a session time a username and password is given to authorise for getting
        // if not, try without authentication
		if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
			$request = $client->get('tdtinfo/resources.json');
		} else {
			$request = $client->get('tdtinfo/resources.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
		}
		$obj = $request->send()->getBody();
	} catch (ClientErrorResponseException $e) {
		// if tried with authentication and it failed 
        // or when tried without authentication and authentication is needed
		if ($e->getResponse()->getStatusCode() == 401) {
			// necessary information is stored in the session object, needed to redo the request after authentication
			$app['session']->set('method','get');
			$app['session']->set('redirect',$hostname.'ui/package');
			$app['session']->set('referer',$hostname.'ui/package');
			return $app->redirect('../../ui/authentication');	
		} else {
	 		$app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
            return $app->redirect('../../ui/error');
	 	}
	}
	
	// transform to a json object
	$jsonobj = json_decode($obj);

	// All the packages and resources will be stored in an array
	// The name of the array is the package, the elements are the resources
	$packages = array();

	// iterating over all the elements in the json object
	foreach ($jsonobj->resources as $key => $value) {
		// filtering the packages because the tdtinfo and the tdtadmin packages are of no interest to the user
		if ($key != "tdtinfo" && $key != "tdtadmin"){
			$packages[$key]= array();

			//getting the resources
			foreach ($jsonobj->resources->$key as $key2 => $value2) {
				$resource[$key]->name = $key2;
				$resource[$key]->documentation = $jsonobj->resources->$key->$key2->documentation;
				array_push($packages[$key], $resource[$key]);
			}

		}		
	}
	$data["packages"] = $packages;
	return $app['twig']->render('packages.twig',$data);
})->value('url', '');