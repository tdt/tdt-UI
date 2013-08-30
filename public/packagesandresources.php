<?php

/**
 * Getting the packages and resources
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Needed for conntecting to the client
use Guzzle\Http\Client;
// Included for catching the 401 errors (authorization needed)
use Guzzle\Http\Exception\ClientErrorResponseException;

// Representing the data in twig.
$app->get('/ui/datasets{url}', function () use ($app, $data) {

    // Create a Guzzle client (to get the data)
    $client = new Client(BASE_URL);

    // Getting the packages in JSON format
    try {
        // Checking if once in a session time a username and password is given to authorise for getting
        // If not, try without authentication
        if ($app['session']->get('userget') == null || $app['session']->get('pswdget') ==null) {
            $request = $client->get(BASE_URL . 'info/datasets.json');
        } else {
            $request = $client->get(BASE_URL . 'info/datasets.json')->setAuth($app['session']->get('userget'),$app['session']->get('pswdget'));
        }
        $obj = $request->send()->getBody();

    } catch (ClientErrorResponseException $e) {
        // If tried with authentication and it failed
        // Or when tried without authentication and authentication is needed
        if ($e->getResponse()->getStatusCode() == 401) {
            // Necessary information is stored in the session object, needed to redo the request after authentication
            $app['session']->set('method','get');
            $app['session']->set('redirect',BASE_URL.'ui/datasets');
            $app['session']->set('referer',BASE_URL.'ui/datasets');
            return $app->redirect(BASE_URL . 'ui/authentication');
        } else {
            $app['session']->set('error',$e->getResponse()->getStatusCode().": ".$e->getResponse()->getReasonPhrase());
            return $app->redirect(BASE_URL . 'ui/error');
        }
    }

    // Transform to a JSON object
    $jsonobj = json_decode($obj);

    // All the packages and resources will be stored in an array
    // The name of the array is the package, the elements are the resources
    $packages = array();

    // Iterating over all the elements in the json object
    foreach ($jsonobj->datasets as $key => $value) {
        // Filtering the packages because the info and the definitions packages are of no interest to the user
        if ($key != "info" && $key != "definitions"){
            $packages[$key]= array();

            // Getting the resources
            foreach ($jsonobj->datasets->$key as $datasetName => $datasetObj) {

                if(!isset($resource[$key])){
                    $resource[$key] = new \stdClass();
                }

                $resource[$key]->name = $datasetName;
                $resource[$key]->documentation = $datasetObj->documentation;
                $resource[$key]->uri = $datasetObj->uri;

                array_push($packages[$key], $resource[$key]);
            }

        }
    }

    $data["packages"] = $packages;
    $data['title'] = 'Dataset management' . TITLE_PREFIX;
    return $app['twig']->render('datasets.twig',$data);
})->value('url', '');