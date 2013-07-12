<?php
 
/**
 * Publishing a file on datatank
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


$app->match('/ui/package/add{url}', function (Request $request) use ($app,$hostname,$data) {

	// Create a client (to get the data)
	$client = new Client($hostname);

	// getting information about creating a CSV file
	try {
		if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
			$request2 = $client->get('tdtinfo/admin.json');
		} else {
			$request2 = $client->get('tdtinfo/admin.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
		}
		$obj = $request2->send()->getBody();
	 } catch (ClientErrorResponseException $e) {
	 	if ($e->getResponse()->getStatusCode() == 401) {
		 	$app['session']->set('method','get');
			$app['session']->set('redirect','../../ui/package/add');
			return $app->redirect('../../ui/authentication');	
	 	}
	 }
	$jsonobj = json_decode($obj);

	$generaltype = $app['session']->get('generaltype');
	if ( $generaltype == 'generic'){
		$type = $app['session']->get('filetype');
		$requiredcreatevariables = $jsonobj->admin->create->generic->$type->requiredparameters;
	}
	else {
		$requiredcreatevariables = $jsonobj->admin->create->$generaltype->requiredparameters;
	}

	$explanationvariables = $jsonobj->admin->create->generic->$type->parameters;

	if (($key = array_search('resource_type', $requiredcreatevariables)) !== false) {
	    unset($requiredcreatevariables[$key]);
	}

	if (($key = array_search('generic_type', $requiredcreatevariables)) !== false) {
	    unset($requiredcreatevariables[$key]);
	}

	// Create a Silex form with all the required fields 
	$form = $app['form.factory']->createBuilder('form');
	$form = $form->add('TargetURI','text',array('label' => "Target URI" ,'constraints' => new Assert\NotBlank(), 'label' => 'TargetURI: destination of the file'));
	foreach ($requiredcreatevariables as $key => $value) {
		$documentation = $explanationvariables->$value;
		$form = $form->add($value,'text',array('constraints' => new Assert\NotBlank(), 'label' => $value.": ".$documentation));
	}

	// for not required parameter (this is an example, yet to be included!!)
	// $form = $form->add('language','text',array('required' => false));

	$form = $form->getForm();

	// If the method is POST, validate the form
	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$formdata = $form->getData();
			
			// making array for the body of the put request
			$body = array();
			foreach ($requiredcreatevariables as $key => $value) {
				$body[$value] = $formdata[$value];
			}

			// setting the already given info to the body (resource type and if necessary general type)
			$body['resource_type'] = $app['session']->get('generaltype');
			if ($body['resource_type'] == 'generic') {
				$body['generic_type'] = $app['session']->get('filetype');
			}

			// initializing a new client
			$client = new Client();

			try{
				// the put request
				if ($app['session']->get('userput') == null || $app['session']->get('pswdput') ==null) {
					$request = $client->put($formdata['TargetURI'],null,$body);
				} else {
					$request = $client->put($formdata['TargetURI'],null,$body)->setAuth($app['session']->get('userput'),$app['session']->get('pswdput'));
				}
				$response = $request->send();
			} catch(ClientErrorResponseException $e) {
				$app['session']->set('method','put');
				$app['session']->set('path',$formdata['TargetURI']);
				$app['session']->set('body',$body);
				$app['session']->set('redirect','../../ui/package');
				return $app->redirect('../../ui/authentication');
			}

			// Redirect to list of packages 	
			return $app->redirect('../../ui/package');
		}
	}

	// display the form
	$data['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$data['title']= "Putting a file";
	$data['header']= "Putting ".$type." file";
	$data['button']= "Add";
	return $app['twig']->render('form.twig', $data);
});
