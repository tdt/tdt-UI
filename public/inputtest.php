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
use Symfony\Component\Form\FormError;


$app->match('/ui/inputtest{url}', function (Request $request) use ($app,$hostname,$data) {
    // getting the input and mapping file (inserted by the user and saved in session-object)
    $fileInput = @file_get_contents($app['session']->get('inputfile'));
    $fileMapping = @file_get_contents($app['session']->get('mappingfile'));

    $form = $app['form.factory']->createBuilder('form',array('input' => $fileInput,'mapping' => $fileMapping,'format' => $app['session']->get('typeinput')));
    $form = $form->add('input','textarea',array('label' => 'Data', 'attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveFile','submit',array('attr' => array('class' => 'btnmapping')));
    $form = $form->add('mapping','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveMappingFile','submit',array('attr' => array('class' => 'btnmapping')));
    $form = $form->add('format','hidden');
    $form = $form->getForm();

    if ('POST' == $request->getMethod()){
        $form->bind($request);
        // Retrieve the form data
        $formdata = $form->getData();

        // Check the validity of the form
        if ($form->isValid()){
            $write = false;
            // Button to save the file was clicked
            if ($form->get('saveFile')->isClicked()){
                $fieldname = "input";
                $write = true;
            }
            // Button to save the mapping file was clicked
            else if ($form->get('saveMappingFile')->isClicked()){
                $fieldname = "mapping";
                $write = true;
            }
            if ($write){
                $filename = $app['session']->get($fieldname.'file');
                $error = writeToFile($filename,$formdata[$fieldname]);
                if ($error !== TRUE){
                    $form->get($fieldname)->addError(new FormError($error));
                }
            }

        }
    }
    $data['hostname'] = $hostname;
    $data['form'] = $form->createView();
    // adding the datafields title and function for the twig file

    return $app['twig']->render('tdtinput.twig', $data);
})->value('url', '');

/**
 * Write data to file, taking into account the filetype for formatting
 * @param $file The filepath to write to
 * @param $data The textstring to write to the file
 * @return if success, TRUE is returned, else an errorstring is returned
 */
function writeToFile($filepath,$data){
    $filename = basename($filepath);
    $dirname = dirname($filepath);
    if (!is_writable($dirname)){
        return $dirname." is not writable";
    }
    if (file_put_contents($filepath, $data) === FALSE){
        return "Write to file ".$filename." failed";
    }
    return TRUE;
}
