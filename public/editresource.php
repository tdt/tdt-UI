<?php

/**
 * editing a resource
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

$app->match('/ui/datasets/edit{url}', function (Request $request) use ($app, $data) {

    // Create a client (to get the data)
    $client = new Client( BASE_URL );
    // getting information about all the resources
    try {
        // checking if once in a session time a username and password is given to authorise for getting
        // if not, try without authentication
        if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
            $request2 = $client->get('tdtadmin/resources/'.$app['session']->get('pathtoresource').'.json');
        } else {
            $request2 = $client->get('tdtadmin/resources/'.$app['session']->get('pathtoresource').'.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
        }
        $obj = $request2->send()->getBody();
     } catch (ClientErrorResponseException $e) {
        // if tried with authentication and it failed
        // or when tried without authentication and authentication is needed
        if ($e->getResponse()->getStatusCode() == 401) {
            // necessary information is stored in the session object, needed to redo the request after authentication
            $app['session']->set('method','get');
            $app['session']->set('redirect', BASE_URL .'ui/resource/edit');
            $app['session']->set('referer', BASE_URL .'ui/resource/edit');
            return $app->redirect(BASE_URL . ' /authentication');
        } else {
            $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
            return $app->redirect(BASE_URL . ' /error');
        }
     }

    // transform to a json object
    $jsonobj = json_decode($obj);

    // getting information about the required parameters a file
    try {
        // checking if once in a session time a username and password is given to authorise for getting
        // if not, try without authentication
        if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
            $request3 = $client->get('tdtinfo/admin.json');
        } else {
            $request3 = $client->get('tdtinfo/admin.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
        }
        $obj2 = $request3->send()->getBody();
    } catch (ClientErrorResponseException $e) {
        // if tried with authentication and it failed
        // or when tried without authentication and authentication is needed
        if ($e->getResponse()->getStatusCode() == 401) {
            // necessary information is stored in the session object, needed to redo the request after authentication
            $app['session']->set('method','get');
            $app['session']->set('redirect', BASE_URL .'ui/package/add');
            $app['session']->set('referer', BASE_URL .'ui/package/add');
            return $app->redirect(BASE_URL . ' /authentication');
        } else {
            $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
            return $app->redirect(BASE_URL . ' /error');
        }
     }
    $jsonobj2 = json_decode($obj2);

    // iterating through all the parameters and the ones that are not edible (in the array notedible defined in index.php) will not be added
    foreach ($jsonobj as $key => $value) {
        foreach ($jsonobj->$key as $key2 => $value2) {
            if(in_array($key2, $app['session']->get('notedible'))){
                // saving what kind of generic type it is (generic, installed,), needed to decide which required variables are needed
                if ($key2 == 'generic_type') {
                    $type = $value2;
                }
                // saving what kind of resource type it is (CSV,JSON,...), needed to decide which required variables are needed
                if ($key2 == 'resource_type') {
                    $generaltype = $value2;
                }
            }else{
                $parameterstobechanged[$key2] = $value2;
            }
        }
    }

    // Getting the required parameters
    if ( $generaltype == 'generic'){
        $requiredcreatevariables = $jsonobj2->admin->create->generic->$type->requiredparameters;
    }
    else {
        $requiredcreatevariables = $jsonobj2->admin->create->$generaltype->requiredparameters;
    }

    // getting documentation over the fields that must be filled in
    if ( $generaltype == 'generic'){
        $explanationvariables = $jsonobj2->admin->create->generic->$type->parameters;
    }
    else {
        $explanationvariables = $jsonobj2->admin->create->$generaltype->parameters;
    }

    // Create a Silex form with all the fields to be changed and the fields already set on the value
    $form = $app['form.factory']->createBuilder('form',$parameterstobechanged);
    $globalindex = 0;
    foreach ($parameterstobechanged as $key => $value) {
        $documentation = $explanationvariables->$key;
        $data['infobuttons'][$globalindex] = $documentation;
        $globalindex++;
        // All the variables that are required
        if (in_array($key, $requiredcreatevariables)) {
            $form = $form->add($key,'text',array('constraints' => new Assert\NotBlank(), 'attr' => array('class' => 'infobutton', 'required' => 'required')));
        }
        // All the variables that are not required
        else {
            $form = $form->add($key,'text',array('required' => false, 'attr' => array('class' => 'infobutton')));
        }

    }

    $form = $form->getForm();

    // If the method is POST, validate the form
    if ('POST' == $request->getMethod()) {
        $form->bind($request);
        if ($form->isValid()) {
            // getting the data from the form
            $formdata = $form->getData();

            // making array for the body of the put request
            $body = array();
            foreach ($parameterstobechanged as $key => $value) {
                $body[$key] = $formdata[$key];
            }

            // initializing a new client
            $client2 = new Client();

            try{
                $path =  BASE_URL ."tdtadmin/resources/".$app['session']->get('pathtoresource');
                // the put request
                // checking if once in a session time a username and password is given to authorise for patching
                // if not, try without authentication
                if ($app['session']->get('userpatch') == null || $app['session']->get('pswdpatch') ==null) {
                    $request = $client2->patch($path,null,$body);
                }
                else{
                    $request = $client2->patch($path,null,$body)->setAuth($app['session']->get('userpatch'),$app['session']->get('pswdpatch'));
                }
                $response = $request->send();
            } catch(ClientErrorResponseException $e) {
                // if tried with authentication and it failed
                // or when tried without authentication and authentication is needed
                if ($e->getResponse()->getStatusCode() == 401) {
                    // necessary information is stored in the session object, needed to redo the request after authentication
                    $app['session']->set('method','patch');
                    $app['session']->set('path',$path);
                    $app['session']->set('body',$body);
                    $app['session']->set('redirect', BASE_URL .'ui/package');
                    $app['session']->set('referer', BASE_URL .'ui/resource/edit');
                    return $app->redirect(BASE_URL . ' /authentication');
                } else {
                    $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
                    return $app->redirect(BASE_URL . ' /error');
                }
            }

            // Redirect to list of packages
            return $app->redirect(BASE_URL . ' /package');
        }
    }

    // display the form
    $data['form'] = $form->createView();
    // adding the datafields title and function for the twig file
    $data['title']= "Changing the data";
    $data['header']= "Changing the data";
    $data['button']= "Change";
    return $app['twig']->render('form.twig', $data);
})->value('url', '');