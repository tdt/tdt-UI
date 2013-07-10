<?php
 
/**
 * editing a resource
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

$app->match('/resource/edit', function (Request $request) use ($app) {

	// Create a client (to get the data)
	$client = new Client(HOSTNAME);
	// getting information about all the resources
	$request2 = $client->get('tdtadmin/resources/'.$app['session']->get('pathtoresource').'.json');
	$obj = $request2->send()->getBody();
	$jsonobj = json_decode($obj);

	foreach ($jsonobj as $key => $value) {
		foreach ($jsonobj->$key as $key2 => $value2) {
			if(in_array($key2, $app['session']->get('notedible'))){

			}else{
				$parameterstobechanged[$key2] = $value2;
			}
		}
	}
	// Create a Silex form with all the fields to be changed and the fields already set on the value
	$form = $app['form.factory']->createBuilder('form',$parameterstobechanged);
	foreach ($parameterstobechanged as $key => $value) {
		$form = $form->add($key,'text',array('constraints' => new Assert\NotBlank()));
	}

	$form = $form->getForm();

	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$data = $form->getData();

			// making array for the body of the put request
			$body = array();
			foreach ($parameterstobechanged as $key => $value) {
				$body[$key] = $data[$key];
			}

			// initializing a new client
			$client2 = new Client();

			try{
				$path = HOSTNAME."tdtadmin/resources/".$app['session']->get('pathtoresource');
				// the put request
				$request = $client2->patch($path,null,$body);
				$response = $request->send();
			} catch(ClientErrorResponseException $e) {
				
			}

			// Redirect to list of packages 	
			return $app->redirect('../../package');
		}
	}

	// display the form
	$twigdata['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$twigdata['title']= "Changing the data";
	$twigdata['header']= "Changing the data";
	$twigdata['button']= "Change";
	return $app['twig']->render('form.twig', $twigdata);

});