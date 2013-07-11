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

// Get The DataTank hostname for use in /ui/package
$hostname = $this->hostname;

// Representing the data in twig.
$app->get('/ui/package{url}', function () use ($app,$hostname,$data) {
	// Create a client (to get the data)
	$client = new Client($hostname);

	// getting the packages in json format
	$request = $client->get('tdtinfo/resources.json');
	$obj = $request->send()->getBody();
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
				array_push($packages[$key], $key2);
			}
		}		
	}
	$data["packages"] = $packages;
	return $app['twig']->render('packages.twig',$data);
});