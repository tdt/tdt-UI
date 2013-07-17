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


$app->match('/ui/inputfile{url}', function (Request $request) use ($app,$hostname,$data) {
	

	// enumerating the possible types of input files
	$possibilities['JSON'] = "JSON";
	$possibilities['XML'] = "XML";
	$possibilities['CSV0'] = "CSV with header row and ; as a delimiter";
	$possibilities['CSV1'] = "CSV with header row and , as a delimiter";
	$possibilities['CSV2'] = "CSV without header row and ; as a delimiter";
	$possibilities['CSV3'] = "CSV without header row and , as a delimiter";

	$form = $app['form.factory']->createBuilder('form');
	$form = $form->add('typeinput','choice',array(
		'choices' => $possibilities, 
		'multiple' => false, 
		'expanded' => false, 
		'label' => false
		)
	);
	$form = $form->add('inputfile','text',array('label' => 'Choose data file', 'required' => false));
	$form = $form->add('mappingfile','text',array('label' => 'Choose mapping file', 'required' => false));
	$form = $form->add('addjobbutton','submit',array('label' => 'Add job', 'attr' => array('class' => 'btn')));
	$form = $form->add('testmappingbutton','submit',array('label' => 'Test mapping', 'attr' => array('class' => 'btn')));
	$form = $form->getForm();

	if ('POST' == $request->getMethod()) {
		$form->bind($request);
		$data2 = $form->getData();
		$app['session']->set('inputfile',$data2['inputfile']);
		$app['session']->set('mappingfile',$data2['mappingfile']);
		$app['session']->set('typeinput',$data2['typeinput']);

		if ($form->get('addjobbutton')->isClicked()){
			return $app->redirect('/ui/addjob');
		}
		else{
			return $app->redirect('/ui/input');
		}
	}

	$data['form'] = $form->createView();
	// adding the datafields title and function for the twig file
	$data['title']= "";
	$data['header']= "";
	return $app['twig']->render('form.twig', $data);

})->value('url', '');