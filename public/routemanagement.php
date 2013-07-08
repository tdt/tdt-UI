<?php
 
/**
 * Shows the list of routes
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

//Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

//Check if another file has already set the cores variable (for example usermanagement.php)
if (!isset($cores)){
	//Fetch routes from file
	$filename = STARTPATH. "app/config/cores.json";
	$routeObject = json_decode(Configurator::stripComments(file_get_contents($filename)));
	$cores = get_object_vars($routeObject);
}

// Render a list of routes using Twig
$app->get('/routes', function () use ($app,$cores) {
	//Give the array with cores to Twig, it contains the routes per core
	$data['cores'] = $cores;
	return $app['twig']->render('routelist.twig',$data);
});