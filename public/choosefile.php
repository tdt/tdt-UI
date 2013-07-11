<?php
 
/**
 * Choosing which resource type is needed
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

$app->match('/ui/package/resourcetype{url}', function (Request $request) use ($app,$hostname) {
	$client = new Client($hostname);

	// getting information about all possible resource types
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
			$app['session']->set('redirect','../../ui/package/resourcetype');
			return $app->redirect('../../ui/authentication');	
	 	}
	 } 

	$jsonobj = json_decode($obj);

	$possibleresourcetype = $jsonobj->admin->create;

	// Create a Silex form with all the possible resourcetypes
	$form = $app['form.factory']->createBuilder('form');

	foreach ($possibleresourcetype as $key => $value) {
		$possibilities[$key] = $key;
	}

	$form = $form->add('Type','choice',array('choices' => $possibilities, 
											'multiple' => false, 
											'expanded' => true,
											'label' => false));

	$form = $form->getForm();

	// If the method is POST, validate the form
	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$formdata = $form->getData();
			
			$app['session']->set('generaltype',$formdata['Type']);

			// Redirect to specific page of the resource type
			if ($formdata['Type'] == generic) {
				$path = '../../ui/package/generictype';
			} else{
				$path = '../../ui/package/add';
			}

			return $app->redirect($path);
		}
	}

	// display the form
	$data['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$data['title']= "Choose resource type";
	$data['header']= "Resource types";
	$data['button']= "Choose";
	return $app['twig']->render('form.twig', $data);

});