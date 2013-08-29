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

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

// included for catching the 401 errors (authorization needed)
use Guzzle\Http\Exception\ClientErrorResponseException;

$app->match('/ui/authentication{url}', function (Request $request) use ($app, $data) {
	$referer = $app['session']->get('referer');

	if ($referer === null){
		$internalreferer = $app['session']->get('internalreferer');
	}
	else{
		$internalreferer = $app['session']->get('referer');
		// If before, a form was already posted with the same referer url,
		// it means the login failed (otherwise, you wouldn't be back at authentication)
		if ($referer == $app['session']->get('formerreferer')){
			// Indicate that the combination is wrong and reset the former referer
			// This is needed to make sure there isn't given an error when you wouldn't post the form
			// and try the same function again at a later time (before the session got reset)
			$wrongcombo = true;
			$app['session']->remove('formerreferer');
		}
	}

	$form = $app['form.factory']->createBuilder('form');
	$form = $form->add('Username','text',array('constraints' => new Assert\NotBlank()));
	$form = $form->add('Password','password',array('constraints' => new Assert\NotBlank()));

	$form = $form->getForm();

	// Two times the authentication form for the same page => wrong combination was given!
	if ($wrongcombo){
		$form->get('Username')->addError(new FormError('Wrong username and/or password'));
	}

	// creating the title
	$title = "Authentication ";
	if ($app['session']->get('method') == 'remove') {
				$title = $title."for deleting";
	} elseif ($app['session']->get('method') == 'getFile') {
				$title = $title."for getting File";
	} elseif ($app['session']->get('method') == 'get') {
				$title = $title."for getting";
	} elseif ($app['session']->get('method') == 'patch') {
				$title = $title."for editing";
	} elseif ($app['session']->get('method') == 'put') {
				$title = $title."for putting";
	}

	if ('POST' == $request->getMethod()) {

		$form->bind($request);
		if ($form->isValid()) {
			// When the form is posted, keep the referer url in session for validation of user/pass combination
			$app['session']->set('formerreferer',$internalreferer);
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
					if ($e->getResponse()->getStatusCode() == 401) {
						return $app->redirect(BASE_URL.'ui/authentication');
					} else{
						$app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
                    	return $app->redirect('../../ui/error');
					}
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
					if ($e->getResponse()->getStatusCode() == 401) {
						return $app->redirect(BASE_URL.'ui/authentication');
					} else{
						$app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
                    	return $app->redirect('../../ui/error');
					}
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
					if ($e->getResponse()->getStatusCode() == 401) {
						return $app->redirect(BASE_URL.'ui/authentication');
					} else{
						$app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
                    	return $app->redirect('../../ui/error');
					}
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
					if ($e->getResponse()->getStatusCode() == 401) {
						return $app->redirect(BASE_URL.'ui/authentication');
					} else{
						$app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
                    	return $app->redirect('../../ui/error');
					}
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
})->value('url', '');