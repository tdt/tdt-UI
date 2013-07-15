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

$app->match('/ui/input{url}', function (Request $request) use ($app,$hostname,$data) {
	$fileMapping = @file_get_contents("/home/leen/Downloads/uitdb-events.xml.spec.ttl");

    $fileInput = @file_get_contents("/home/leen/Downloads/uitdb-events-test.xml");
    // echo "<pre>";
    // print_r($fileInput);
    $defaultData = array('Input' => $fileInput,'Mapping' => $fileMapping);

    $form = $app['form.factory']->createBuilder('form',$defaultData);
    $form = $form->add('Input','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('Mapping','textarea',array('attr' => array('cols' => "100", 'rows' => "500")));
    $form = $form->getForm();

    $data['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$data['title']= "";
	$data['header']= "";
	$data['button']= "Change";

    if ('POST' == $request->getMethod()){
        $form->bind($request);
        // Retrieve the form data
        $formdata = $form->getData();

        // Check the validity of the form
        if ($form->isValid()){
            // Button to save the file was clicked
            if ($form->get('saveFile')->isClicked()){

            }
            // Button to save the mapping file was clicked
            else if ($form->get('saveMappingFile')->isClicked()){

            }
            // Else, the usual submit button was used
            else{
                
            }

        }
    }

	return $app['twig']->render('form.twig', $data);
	

})->value('url', '');