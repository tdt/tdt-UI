<?php
 
/**
 * Making a CSV file
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

// Create a client (to get the data)
$client = new Client(HOSTNAME);

// getting information about creating a CSV file
$request = $client->get('tdtinfo/admin.json');
$obj = $request->send()->getBody();
$jsonobj = json_decode($obj);

$app->match('/package/CVSadd', function (Request $request) use ($jsonobj,$app) {
	$type = $app['session']->get('type');
	$requiredcreatevariables = $jsonobj->admin->create->generic->$type->requiredparameters;

	// Create a Silex form with all the required fields 
	$form = $app['form.factory']->createBuilder('form');
	$form = $form->add('TargetURI','text',array('label' => "Target URI" ,'constraints' => new Assert\NotBlank()));
	foreach ($requiredcreatevariables as $key => $value) {
		$form = $form->add($value,'text',array('constraints' => new Assert\NotBlank()));
	}

	// for not required parameter (this is an example, yet to be included!!)
	$form = $form->add('username','text',array('required' => false));

	$form = $form->getForm();

	// If the method is POST, validate the form
	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$data = $form->getData();
			
			// making array for the body of the put request
			$body = array();
			foreach ($requiredcreatevariables as $key => $value) {
				$body[$value] = $data[$value];
			}

			// initializing a new client
			$client = new Client();

			try{
				// the put request
				$request = $client->put($data['TargetURI'],null,$body);
				$response = $request->send();
			} catch(BadResponseException $e) {
				echo $e->getMessage();
			}

			// Redirect to list of packages 	
			return $app->redirect('../../package');
		}
	}

	// display the form
	$twigdata['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$twigdata['title']= "Putting a file";
	$twigdata['header']= "Putting CSV file";
	$twigdata['button']= "Add";
	return $app['twig']->render('form.twig', $twigdata);
});

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


