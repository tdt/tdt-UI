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

$app->match('/ui/package/resourcetype{url}', function (Request $request) use ($app,$hostname,$data) {
    $client = new Client($hostname);

    // getting information about all possible resource types
    try {
        if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
            // if not authenticated before, try without authentication, maybe no authentication is needed
            $request2 = $client->get('tdtinfo/admin.json');
        } else {
            // if already authenticated for the get method, use the information
            $request2 = $client->get('tdtinfo/admin.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
        }
        $obj = $request2->send()->getBody();
    } catch (ClientErrorResponseException $e) {
        // Authentication is needed
        if ($e->getResponse()->getStatusCode() == 401) {
            // necessary information is stored in the session object, needed to redo the request after authentication
            $app['session']->set('method','get');
            $app['session']->set('redirect',$hostname.'ui/package/resourcetype');
            $app['session']->set('referer',$hostname.'ui/package/resourcetype');
            return $app->redirect('../../ui/authentication');   
        } else {
            echo $e->getResponse()->getMessage();
        }
    } 
    
    // transform to a json object
    $jsonobj = json_decode($obj);

    // getting the possible resourcetypes
    $possibleresourcetype = $jsonobj->admin->create;

    // create a Silex form with all the possible resourcetypes
    $form = $app['form.factory']->createBuilder('form');

    // convert the possible resources needed for the form
    foreach ($possibleresourcetype as $key => $value) {
        $possibilities[$key] = $key;
    }

    // making the form
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
            
            // checking the general type that was given by the user, needed for redirection
            $app['session']->set('generaltype',$formdata['Type']);

            // Redirect to specific page of the resource type
            if ($formdata['Type'] == generic) {
                // In generic you have to choose a specific filetype
                $path = '../../ui/package/generictype';
            } else{
                // for the types installed and remote, you don't have to chosse a specific filetype
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

})->value('url', '');