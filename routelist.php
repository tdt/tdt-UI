<?php
 
/**
 * Shows the list of routes
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

error_reporting(E_ALL);
ini_set('display_errors', True);

require_once 'vendor/autoload.php';
require_once 'ConfigLoader.php';

// Making Silex application
$app = new Silex\Application();
$app['debug'] = true;

//Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/views',
));

//Make a config loader
$configloader = new ConfigLoader();

//Fetch users from file (existence is already checked when loading configurationfiles)
$filename = $configloader->getSettings("startpath"). "app/config/cores.json";
$routeObject = json_decode(ConfigLoader::stripComments(file_get_contents($filename)));
$cores = get_object_vars($routeObject);

// Representing the data in twig.
$app->get('/routes', function () use ($app,$cores) {
	$data = array();
	$data['cores'] = $cores;
	return $app['twig']->render('routelist.twig',$data);
});

// running the application
$app->run();


