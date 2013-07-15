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

$app->get('/ui/inputfile{url}', function () use ($app,$hostname,$data) {
	$form = $app['form.factory']->createBuilder('form');
	$form = $form->add('Chooseinputfile','file');
	$form = $form->add('Choose mapping file','file');
	$form = $form->getForm();

})->value('url', '');