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

$app->get('/ui/login{url}', function(Request $request) use ($app,$data,$check_url) {
	$data['error'] = $app['security.last_error']($request);
	$data['last_username'] = $app['session']->get('_security.last_username');
	$data['checkurl'] = $check_url;
    return $app['twig']->render('login.twig', $data);
});

// $app->match('/ui/login_check', function() use ($app) {
// 	$app->redirect($check_path)
// });


$app->match('/ui/authentication{url}', function (Request $request) use ($app,$data) {
	$form = $app['form.factory']->createBuilder('form');
	
	$form = $form->add('Username','text',array('constraints' => new Assert\NotBlank()));
	$form = $form->add('Password','password',array('constraints' => new Assert\NotBlank()));

	$form = $form->getForm();

	$title = "Authentication";

	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		if ($form->isValid()) {
			// getting the data from the form
			$formdata = $form->getData();

			$client = new Client();

			// if a delete request must be authorized
			if ($app['session']->get('method') == 'remove') {
				$title = $title."for deleting";
				$app['session']->set('userrm',$formdata['Username']);
				$app['session']->set('pswdrm',$formdata['Password']);
				try {
					$request = $client->delete($app['session']->get('path'))->setAuth($formdata['Username'],$formdata['Password']);
					$response = $request->send();
				} catch (ClientErrorResponseException $e) {
					return $app->redirect('../../ui/authentication');
				}
				
			}
			elseif ($app['session']->get('method') == 'getFile') {
				$title = $title."for getting File";
				$app['session']->set('userget',$formdata['Username']);
				$app['session']->set('pswdget',$formdata['Password']);
				try {
					$request = $client->get($app['session']->get('path'))->setAuth($formdata['Username'],$formdata['Password']);
					$response = $request->send()->getBody();
				} catch (ClientErrorResponseException $e) {
					return $app->redirect('../../ui/authentication');
				}
				// return the response (the json or php file)
				return $response;
			}
			elseif ($app['session']->get('method') == 'get') {
				$title = $title."for getting";
				$app['session']->set('userget',$formdata['Username']);
				$app['session']->set('pswdget',$formdata['Password']);
			}
			elseif ($app['session']->get('method') == 'patch') {
				$title = $title."for editing";
				$app['session']->set('userpatch',$formdata['Username']);
				$app['session']->set('pswdpatch',$formdata['Password']);
				try {
					$request = $client->patch($app['session']->get('path'),null,$app['session']->get('body'))->setAuth($formdata['Username'],$formdata['Password']);
					$response = $request->send();
				} catch (ClientErrorResponseException $e) {
					return $app->redirect('../../ui/authentication');
				}
			}
			elseif ($app['session']->get('method') == 'put') {
				$title = $title."for putting";
				$app['session']->set('userput',$formdata['Username']);
				$app['session']->set('pswdput',$formdata['Password']);
				try {
					$request = $client->put($app['session']->get('path'),null,$app['session']->get('body'))->setAuth($formdata['Username'],$formdata['Password']);
					$response = $request->send();
				} catch (ClientErrorResponseException $e) {
					return $app->redirect('../../ui/authentication');
				}
			}

			return $app->redirect($app['session']->get('redirect'));
		}
	}

	// display the form
	$data['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$data['title']= "Authentication";
	$data['header']= $title;
	$data['button']= "OK";
	return $app['twig']->render('form.twig', $data);
});