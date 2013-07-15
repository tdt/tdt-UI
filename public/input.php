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

// Used to write json to file, formatted to be read by humans
require_once __DIR__.'/../src/nicejson-php/nicejson.php';

$app->match('/ui/input.about', function (Request $request) use ($app,$hostname,$data) {
	$fileMapping = @file_get_contents("/home/leen/Downloads/uitdb-events.xml.spec.ttl");

    $fileInput = @file_get_contents("/home/leen/Downloads/uitdb-events-test.xml");
    // echo "<pre>";
    // print_r($fileInput);
    $defaultData = array('Input' => $fileInput,'Mapping' => $fileMapping);

    $form = $app['form.factory']->createBuilder('form',$defaultData);
    $form = $form->add('input','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveFile','submit');
    $form = $form->add('mapping','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveMappingFile','submit');
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
                $filename = "/Volumes/Data/tmp/test.json";
                $filtype = "JSON";
                $error = writeToFile($filename,$formdata['input'],"json");
                if ($error !== TRUE){
                    return $error;
                }
                return "saveFile";
            }
            // Button to save the mapping file was clicked
            else if ($form->get('saveMappingFile')->isClicked()){
                return "test2";
            }
            // Else, the usual submit button was used
            else{
                return "test3";
            }

        }
    }

	return $app['twig']->render('form.twig', $data);
})->value('url', '');

/**
 * Write data to file, taking into account the filetype for formatting
 * @param $file The filepath to write to
 * @param $data The textstring to write to the file
 * @param $type The type of formatting or null if no formatting is needed (json, xml or csv)
 * @return if success, TRUE is returned, else an errorstring is returned
 */
function writeToFile($filepath,$data,$type = null){
    $filename = basename($filepath);
    $dirname = dirname($filepath);
    if (!is_writable($dirname)){
        return $dirname." is not writable";
    }
    if (strcmp(trim(strtolower($type)),"json") === 0){
        $returnval = file_put_contents($filename, json_format($data));
    }
    else{
        $returnval = file_put_contents($filename, $data);
    }
    if ($returnval === FALSE){
        return "Write to file ".$filename." failed";
    }
    return TRUE;
}