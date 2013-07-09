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

$client = new Client(HOSTNAME);

// getting information about all possible generic 
$request = $client->get('tdtinfo/admin.json');
$obj = $request->send()->getBody();
$jsonobj = json_decode($obj);

$types =$jsonobj->admin->create->generic;

$type = "test";

$app->match('/package/generictype', function (Request $request) use ($types,$app) {

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
			$app['session']->set('filetype',$data['Type']);
			return $app->redirect('../../package/CVSadd');
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