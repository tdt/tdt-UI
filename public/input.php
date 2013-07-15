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


$app->match('/ui/input{url}', function (Request $request) use ($app,$hostname,$data) {
    // getting the input and mapping file (inserted by the user and saved in session-object)
	$fileInput = @file_get_contents($app['session']->get('inputfile'));
    $fileMapping = @file_get_contents($app['session']->get('mappingfile'));

    $form = $app['form.factory']->createBuilder('form',array('input' => $fileInput,'mapping' => $fileMapping));
    $form = $form->add('input','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveFile','submit',array('attr' => array('class' => 'btn')));
    $form = $form->add('mapping','textarea',array('attr' => array('cols' => "100", 'rows' => "200", 'style' => "width: 100%; height: 110px;")));
    $form = $form->add('saveMappingFile','submit',array('attr' => array('class' => 'btn')));
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
            // Else, the usual submit button was used
            else{
                // defining the extract part
                $filetype = $app['session']->get('typeinput');
                switch ($filetype) {
                    case 'CSV0':
                        $extract = array("type"=> "CSV","delimiter"=> ";","has_header_row"=> "1");
                        break;
                    case 'CSV1':
                        $extract = array("type"=> "CSV","delimiter"=> ",","has_header_row"=> "1");
                        break;
                    case 'CSV2':
                        $extract = array("type"=> "CSV","delimiter"=> ";","has_header_row"=> "0");
                        break;
                    case 'CSV3':
                        $extract = array("type"=> "CSV","delimiter"=> ",","has_header_row"=> "0");
                        break;
                    case 'XML':
                        $extract = array("type"=> "XML");
                        break;
                    case 'JSON':
                        $extract = array("type"=> "JSON");
                        break;
                    default:
                }
                // $config->mapping = urlencode($formdata['mapping']);
                // $config->extract = $extract;
                // $config->chunk = ($formdata['input']);
                $config = array(
                  "mapping"=> htmlentities($formdata['mapping']),
                  "extract"=> $extract,
                  "chunk"=> ($formdata['input'])
                );
                //$config = json_encode($config);
                $client = new Client();
                $request = $client->post($hostname."tdtinput/test",null,$config);
                $response = $request->send();                
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

    $data['form'] = $form->createView();
    // adding the datafields title and function for the twig file
    $data['title']= "";
    $data['header']= "";
    $data['button']= "Change";

	return $app['twig']->render('form.twig', $data);
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
