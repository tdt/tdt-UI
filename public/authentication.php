<?php
 
/**
 * asking the username and password of the user
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

$app->get('/ui/login{url}', function(Request $request) use ($app) {
    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

// $app->post('/admin/login_check', function(Request $request) use ($app) {
//     return $app->redirect('/');
// });

$app->match('/zefse', function (Request $request) use ($app) {
	$form = $app['form.factory']->createBuilder('form');
	
	$form = $form->add('Username','text',array('constraints' => new Assert\NotBlank()));
	$form = $form->add('Password','password',array('constraints' => new Assert\NotBlank()));

	$form = $form->getForm();

	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$data = $form->getData();
			$app['session']->set('Username',$data['Username']);
			$app['session']->set('Password',$data['Password']);
			return $app->redirect($app['session']->get('redirect'));
		}
	}

	// display the form
	$twigdata['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$twigdata['title']= "Authentication";
	$twigdata['header']= "Authentication";
	$twigdata['button']= "OK";
	return $app['twig']->render('form.twig', $twigdata);
});