<?php
 
/**
 * Shows the list of routes
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

require_once STARTPATH.'app/core/configurator.php';

//Fetch users from file (existence is already checked when loading configurationfiles)
$filename = STARTPATH. "app/config/cores.json";
$routeObject = json_decode(Configurator::stripComments(file_get_contents($filename)));
$cores = get_object_vars($routeObject);

// Render a list of routes using Twig
$app->get('/routes', function () use ($app,$cores) {
	$data = array();
	$data['cores'] = $cores;
	return $app['twig']->render('routelist.twig',$data);
});