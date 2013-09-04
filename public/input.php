<?php

/**
 * Input the mapping file and input file
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Needed for conntecting to the client
use Guzzle\Http\Client;
// included for catching the 401 errors (authorization needed)
use Guzzle\Http\Exception\ClientErrorResponseException;
use Symfony\Component\HttpFoundation\Request;


// Define type of input files
$input_types = array(
    'JSON' => array(
        'value' => 'JSON',
        'text' => 'JSON',
        'options' => array(
            'type' => 'JSON',
        ),
    ),
    'XML' => array(
        'value' => 'XML',
        'text' => 'XML',
        'options' => array(
            'type' => 'XML',
        ),
    ),
    'CSV0' => array(
        'value' => 'CSV0',
        'text' => 'CSV with header row and ; as a delimiter',
        'options' => array(
            'type' => 'CSV',
            'delimiter' => ';',
            'has_header_row' => '1',
        ),
    ),
    'CSV1' => array(
        'value' => 'CSV1',
        'text' => 'CSV with header row and , as a delimiter',
        'options' => array(
            'type' => 'CSV',
            'delimiter' => ',',
            'has_header_row' => '1',
        ),
    ),
    'CSV2' => array(
        'value' => 'CSV2',
        'text' => 'CSV without header row and ; as a delimiter',
        'options' => array(
            'type' => 'CSV',
            'delimiter' => ';',
            'has_header_row' => '0',
        ),
    ),
    'CSV3' => array(
        'value' => 'CSV3',
        'text' => 'CSV without header row and , as a delimiter',
        'options' => array(
            'type' => 'CSV',
            'delimiter' => ',',
            'has_header_row' => '0',
        ),
    ),
);

// Index route
$app->match('/ui/input', function (Request $request) use ($app, $data, $input_types) {

    $data['types'] = $input_types;

    if ($request->getMethod() == 'POST') {

        $app['session']->set('inputfile', $request->get('inputfile'));
        $app['session']->set('mappingfile', $request->get('mappingfile'));
        $app['session']->set('type', $request->get('type'));

        if($request->get('addjob')){
            return $app->redirect(BASE_URL . 'ui/input/job');
        }else{
            return $app->redirect(BASE_URL . 'ui/input/test');
        }
    }

    $data['title']= "Input Management" . TITLE_PREFIX;
    return $app['twig']->render('input/form.twig', $data);

});

// Test route
$app->match('/ui/input/test', function (Request $request) use ($app, $data, $input_types) {

    // Getting the input and mapping file (inserted by the user and saved in session-object)
    $data['inputfile'] = @file_get_contents($app['session']->get('inputfile'));
    $data['mappingfile'] = @file_get_contents($app['session']->get('mappingfile'));
    $data['type'] = $input_types[$app['session']->get('type')];

    $data['title']= "Test Mapping" . TITLE_PREFIX;

    return $app['twig']->render('input/test.twig', $data);
});

// Add the job
$app->get('/ui/input/job', function (Request $request) use ($app, $data, $input_types) {

    $data['inputfile'] = $app['session']->get('inputfile');
    $data['mappingfile'] = $app['session']->get('mappingfile');
    $data['type'] = $input_types[$app['session']->get('type')];

    $data['title']= "Add a job" . TITLE_PREFIX;
    return $app['twig']->render('input/job.twig', $data);
});