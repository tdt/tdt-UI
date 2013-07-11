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



$app->match('/ui/package/generictype', function (Request $request) use ($app) {
	$client = new Client(HOSTNAME);

	// getting information about all possible generic resource types
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
			$app['session']->set('redirect','../../ui/package/generictype');
			return $app->redirect('../../ui/authentication');	
	 	}
	 }

	$jsonobj = json_decode($obj);

	$types =$jsonobj->admin->create->generic;

	// Create a Silex form with all the possible resourcetypes
	$form = $app['form.factory']->createBuilder('form');

	foreach ($types as $key => $value) {
		$possibilities[$key] = $key;
	}

	$form = $form->add('Type','choice',array('choices' => $possibilities, 'multiple' => false, 'expanded' => false, 'label' => false));

	$form = $form->getForm();

	// If the method is POST, validate the form
	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$data = $form->getData();
			echo $data['Type'];
			$app['session']->set('filetype',$data['Type']);
			return $app->redirect('../../ui/package/add');
		}
	}

	// display the form
	$twigdata['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$twigdata['title']= "Choose file type";
	$twigdata['header']= "file types";
	$twigdata['button']= "Choose";
	return $app['twig']->render('form.twig', $twigdata);

});