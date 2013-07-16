<?php
 
/**
 * Add a new job to tdtinput
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

$app->get('/ui/addjob{url}', function (Request $request) use ($app,$hostname,$data) {
	$data['inputfiletype'] = $app['session']->get('typeinput');
	$data['inputfilepath'] = $app['session']->get('inputfile');
	$data['mappingfilepath'] = $app['session']->get('mappingfile');
	return $app['twig']->render('addjob.twig', $data);
})->value('url', '');