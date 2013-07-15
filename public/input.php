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

$app->get('/ui/input{url}', function () use ($app,$hostname,$data) {
	$fileMapping = @file_get_contents("/home/leen/Downloads/uitdb-events.xml.spec.ttl");

    $fileInput = @file_get_contents("/home/leen/Downloads/uitdb-events-test.xml");
    // echo "<pre>";
    // print_r($fileInput);

    $form = $app['form.factory']->createBuilder('form',array('Input' => $fileInput,'Mapping' => $fileMapping));
    $form = $form->add('bestand','file');
    $form = $form->add('Input','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveFile','submit');
    $form = $form->add('Mapping','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveMappingFile','submit');
    $form = $form->getForm();

    $data['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$data['title']= "";
	$data['header']= "";
	$data['button']= "Change";
	return $app['twig']->render('form.twig', $data);
	

})->value('url', '');