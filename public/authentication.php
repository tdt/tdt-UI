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

$app->get('/login', function(Request $request) use ($app) {
    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

// $app->post('/admin/login_check', function(Request $request) use ($app) {
//     return $app->redirect('/');
// });

$app->match('/ui/authentication', function (Request $request) use ($app) {
	$form = $app['form.factory']->createBuilder('form');
	
	$form = $form->add('Username','text',array('constraints' => new Assert\NotBlank()));
	$form = $form->add('Password','password',array('constraints' => new Assert\NotBlank()));

	$form = $form->getForm();

	$title = "Authentication";

	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$data = $form->getData();

			$client = new Client();

			// if a delete request must be authorized
			if ($app['session']->get('method') == 'remove') {
				$title = $title."for deleting";
				$app['session']->set('userrm',$data['Username']);
				$app['session']->set('pswdrm',$data['Password']);
				try {
					$request = $client->delete($app['session']->get('path'))->setAuth($data['Username'],$data['Password']);
					$response = $request->send();
				} catch (ClientErrorResponseException $e) {
					$app->redirect('../../ui/authentication');
				}
				
			}
			elseif ($app['session']->get('method') == 'getFile') {
				$title = $title."for getting File";
				$app['session']->set('userget',$data['Username']);
				$app['session']->set('pswdget',$data['Password']);
				try {
					$request = $client->get($app['session']->get('path'))->setAuth($data['Username'],$data['Password']);
					$response = $request->send()->getBody();
				} catch (ClientErrorResponseException $e) {
					$app->redirect('../../ui/authentication');
				}
				// return the response (the json or php file)
				return $response;
			}
			elseif ($app['session']->get('method') == 'get') {
				$title = $title."for getting";
				$app['session']->set('userget',$data['Username']);
				$app['session']->set('pswdget',$data['Password']);
			}
			elseif ($app['session']->get('method') == 'patch') {
				$title = $title."for editing";
				$app['session']->set('userpatch',$data['Username']);
				$app['session']->set('pswdpatch',$data['Password']);
				try {
					$request = $client->patch($app['session']->get('path'),null,$app['session']->get('body'))->setAuth($data['Username'],$data['Password']);
					$response = $request->send();
				} catch (ClientErrorResponseException $e) {
					return $app->redirect('../../ui/authentication');
				}
			}
			elseif ($app['session']->get('method') == 'put') {
				$title = $title."for putting";
				$app['session']->set('userput',$data['Username']);
				$app['session']->set('pswdput',$data['Password']);
				try {
					$request = $client->put($app['session']->get('path'),null,$app['session']->get('body'))->setAuth($data['Username'],$data['Password']);
					$response = $request->send();
				} catch (ClientErrorResponseException $e) {
					return $app->redirect('../../ui/authentication');
				}
			}

			return $app->redirect($app['session']->get('redirect'));
		}
	}

	// display the form
	$twigdata['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$twigdata['title']= "Authentication";
	$twigdata['header']= $title;
	$twigdata['button']= "OK";
	return $app['twig']->render('form.twig', $twigdata);
});