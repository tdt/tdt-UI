<?php
 
/**
 * Making a CSV file
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Making Silex applicatie
require_once 'vendor/autoload.php';
$app = new Silex\Application();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// registering the silex form service
use Silex\Provider\FormServiceProvider;

// So you can see the errors made
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
'twig.path' => __DIR__.'/views',
),new FormServiceProvider());

//needed for conntecting to the client
use Guzzle\Http\Client;
//needed for the PUT request
use Guzzle\Http\Message;
use Guzzle\Http\Query;

// Create a client (to get the data)
$client = new Client('http://localhost/tdt/start/public/');

// getting information about creating a CSV file
$request = $client->get('tdtinfo/admin.json');
$obj = $request->send()->getBody();
$jsonobj = json_decode($obj);

$requiredcreatevariables = $jsonobj->admin->create->generic->CSV->requiredparameters;

foreach ($requiredcreatevariables as $key => $value) {
  	echo $value."</br>";
}

$app->get('/form', function () use ($requiredcreatevariables,$app) {
$data = array();
$data["requiredcreatevariables"] = $requiredcreatevariables;
return $app['twig']->render('puttingFile.twig',$data);
});

// running the application
$app->run();

// //the put request
// $client = new Client();

// try {
// 	 $request = $client->put('http://localhost/tdt/start/public/tdtadmin/resources/package4/whiii',null,array(
//  						'resource_type' => 'generic',
//  						'generic_type' => 'CSV',
//  						'documentation' => 'The same but differentyesfefs',
// 						'uri' => '/home/leen/tdt/start/public/resources/dataJul-4-2013.csv')); 

// 	 $response = $request->send();
// 	 echo $response->getBody();
// } catch(BadResponseException $e) {
// 	echo $e->getMessage();
// }


