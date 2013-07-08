<?php
 
/**
 * Getting the packages and resources
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Making Silex applicatie
require_once 'vendor/autoload.php';
$app = new Silex\Application();

// So you can see the errors made
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
'twig.path' => __DIR__.'/views',
));

//needed for conntecting to the client
use Guzzle\Http\Client;

// Create a client (to get the data)
$client = new Client('http://localhost/tdt/start/public/');

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

// Representing the data in twig.
$app->get('/package', function () use ($packages,$app) {
$data = array();
$data["packages"] = $packages;
return $app['twig']->render('packages.twig',$data);
});

// running the application
$app->run();
