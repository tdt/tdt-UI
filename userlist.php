<?php
 
/**
 * Shows the list of users
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Making Silex application
require_once 'vendor/autoload.php';
$app = new Silex\Application();
$app['debug'] = true;

//Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/views',
));

//Get settings
$settings = json_decode(file_get_contents(__DIR__.'/config/settings.json'));

//Fetch users from file (existence is already checked when loading configurationfiles)
$filename = $settings->startpath. "app/config/auth.json";

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


