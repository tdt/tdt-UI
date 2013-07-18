<?php
 
/**
 * Making a form for optional parameters
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


$app->match('/ui/package/optionaladd{url}', function (Request $request) use ($app,$hostname,$data) {
    // Create a client (to get the data)
    $client = new Client($hostname);

    // getting information about creating a file
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
            $app['session']->set('redirect',$hostname.'ui/package/add');
            $app['session']->set('referer',$hostname.'ui/package/add');
            return $app->redirect('../../ui/authentication');   
        } else {
            $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
            return $app->redirect('../../ui/error');
        }
    }
    // transform to a json object
    $jsonobj = json_decode($obj);

    $generaltype = $app['session']->get('generaltype');
    if ( $generaltype == 'generic'){
        $type = $app['session']->get('filetype');
        $variables = $jsonobj->admin->create->generic->$type->parameters;
        $requiredcreatevariables = $jsonobj->admin->create->generic->$type->requiredparameters;
        $explanationvariables = $jsonobj->admin->create->generic->$type->parameters;
    }
    else {
        $variables = $jsonobj->admin->create->$generaltype->parameters;
        $requiredcreatevariables = $jsonobj->admin->create->$generaltype->requiredparameters;
        $explanationvariables = $jsonobj->admin->create->$generaltype->parameters;
    }

    // Create a Silex form with all the required fields 
    $globalindex = 0;
    $form = $app['form.factory']->createBuilder('form');
    foreach ($variables as $key => $value) {
        if (array_search($key, $requiredcreatevariables) === false) {
            $documentation = $explanationvariables->$key;
            $form = $form->add($key,'text',array('required' => false, 'attr' => array('class' => 'infobutton')));
            $data['infobuttons'][$globalindex] = $documentation;
            $globalindex++; 
        }
        
    }

    $form = $form->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);           
        if ($form->isValid()) {
            // getting the data from the form
            $formdata = $form->getData();

            // making array for the body of the put request
            $body = array();
            foreach ($requiredcreatevariables as $key => $value) {
                $body[$value] = $app['session']->get($value);
            }

            foreach ($variables as $key => $value) {
                if ($formdata[$key] != NULL) {
                    $body[$key] = $formdata[$key];
                }
                
            }
            
            // setting the already given info to the body (resource type and if necessary general type)
            $body['resource_type'] = $app['session']->get('generaltype');
            if ($body['resource_type'] == 'generic') {
                $body['generic_type'] = $app['session']->get('filetype');
            }

            // initializing a new client
            $client = new Client();

            $targeturi = $app['session']->get('TargetURI');
            // the put request
            try{
                // checking if once in a session time a username and password is given to authorise for getting
                // if not, try without authentication
                if ($app['session']->get('userput') == null || $app['session']->get('pswdput') ==null) {
                    $request = $client->put($targeturi,null,$body);
                } else {
                    $request = $client->put($targeturi,null,$body)->setAuth($app['session']->get('userput'),$app['session']->get('pswdput'));
                }
                $response = $request->send();
            } catch(ClientErrorResponseException $e) {
                // if tried with authentication and it failed 
                // or when tried without authentication and authentication is needed
                if ($e->getResponse()->getStatusCode() == 401) {
                    // necessary information is stored in the session object, needed to redo the request after authentication
                    $app['session']->set('method','put');
                    $app['session']->set('path',$targeturi);
                    $app['session']->set('body',$body);
                    $app['session']->set('redirect',$hostname.'ui/package');
                    $app['session']->set('referer',$hostname.'ui/package/add');
                    return $app->redirect('../../ui/authentication');
                } else {
                    $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
                    return $app->redirect('../../ui/error');
                }
            }

            // Redirect to list of packages     
            return $app->redirect('../../ui/package');
        }
    }

    // display the form
    $data['form'] = $form->createView();
    // adding the datafields title and function for the twig file
    $data['title']= "Putting a file";
    $data['header']= "Adding optional parameters";
    $data['button']= "Change";
    return $app['twig']->render('form.twig', $data);

})->value('url', '');