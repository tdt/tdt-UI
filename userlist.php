<?php
 
/**
 * Shows the list of users
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
$filename = $configloader->getSettings("startpath"). "app/config/auth.json";

$users = json_decode(file_get_contents($filename));
$usernames = array_keys(get_object_vars($users));

// Representing the data in twig.
$app->get('/users', function () use ($app,$usernames) {
	$data = array();
	$data['usernames'] = $usernames;
	return $app['twig']->render('userlist.twig',$data);
});

// running the application
$app->run();


