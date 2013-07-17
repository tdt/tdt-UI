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



$app->match('/ui/package/generictype{url}', function (Request $request) use ($app,$hostname,$data) {
    $client = new Client($hostname);

    // getting information about all possible generic resource types
    try {
        // checking if once in a session time a username and password is given to authorise for getting
        // if not, try without authentication
        if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
            $request2 = $client->get('tdtinfo/admin.json');
        } else {
            $request2 = $client->get('tdtinfo/admin.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
        }
        $obj = $request2->send()->getBody();
    } catch (ClientErrorResponseException $e) {
        // if tried with authentication and it failed 
        // or when tried without authentication and authentication is needed
        if ($e->getResponse()->getStatusCode() == 401) {
            // necessary information is stored in the session object, needed to redo the request after authentication
            $app['session']->set('method','get');
            $app['session']->set('redirect',$hostname.'ui/package/generictype');
            $app['session']->set('referer',$hostname.'ui/package/generictype');
            return $app->redirect('../../ui/authentication');   
        } else {
            $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
            return $app->redirect('../../ui/error');
        }
    }

    // transform to a json object
    $jsonobj = json_decode($obj);

    $types =$jsonobj->admin->create->generic;

    // Create a Silex form with all the possible resourcetypes
    $form = $app['form.factory']->createBuilder('form');

    foreach ($types as $key => $value) {
        $possibilities[$key] = $key;
    }

    // adding a dropdown menu with the multiple possibilities
    $form = $form->add('Type','choice',array('choices' => $possibilities, 'multiple' => false, 'expanded' => false, 'label' => false));

    $form = $form->getForm();

    // If the method is POST, validate the form
    if ('POST' == $request->getMethod()) {
        $form->bind($request);
        if ($form->isValid()) {
            // getting the data from the form
            $formdata = $form->getData();
            // saving the filetype for the postrequest that is executed later
            $app['session']->set('filetype',$formdata['Type']);
            return $app->redirect('../../ui/package/add');
        }
    }

    // display the form
    $data['form'] = $form->createView();
    // adding the datafields title and function for the twig file
    $data['title']= "Choose file type";
    $data['header']= "file types";
    $data['button']= "Choose";
    return $app['twig']->render('form.twig', $data);

})->value('url', '');