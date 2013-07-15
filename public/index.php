<?php
 
/**
 * Index page
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

require_once '../vendor/autoload.php';

//Construct the Silex application
$app = new Silex\Application();

if (defined('ENVIRONMENT') && strcmp(ENVIRONMENT,'development') == 0){
    $app['debug'] = true;
}

// Website document root
define('UIDOCROOT', __DIR__.DIRECTORY_SEPARATOR);

// Vendor directory
define('UIVENDORPATH', realpath(__DIR__.'/../vendor/').DIRECTORY_SEPARATOR);

// Path to the local tdt-start folder
define("STARTPATH", __DIR__.'/../../../../');

//Register the Twig Service Provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => UIDOCROOT.'views'
));

// Register the Form Service Provider
$app->register(new Silex\Provider\FormServiceProvider());

// Register the Validator and Translation Service Providers
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

// Register the session service provider object
$app->register(new Silex\Provider\SessionServiceProvider());

// Get The DataTank hostname for use in /ui/package
$hostname = $this->hostname.$this->subdir;
$data['relpath'] = '/'.$this->subdir.'ui/';

// must be included first
require_once 'authentication.php';

// If root is asked, redirect to the resource management
$app->get('/ui{url}', function () use ($app) {
    return $app->redirect('ui/package');
})->value('url', '');

// The parameters that cannot be edited
$app['session']->set('notedible',array('generic_type' => 'generic_type', 
                                        'resource_type' => 'resource_type',
                                        'columns' => 'columns',
                                        'column_aliases' => 'column_aliases'));

//start with resources management
require_once 'packagesAndResources.php';
require_once 'usermanagement.php';
require_once 'routemanagement.php';
require_once 'choosefile.php';
require_once 'generictypes.php';
require_once 'puttingfile.php';
require_once 'editPackagesAndResources.php';
require_once 'editResource.php';
require_once 'inputfile.php';
require_once 'input.php';


// Make sure REQUEST_URI starts with a slash. This is needed for Silex to work properly.
if (strpos($_SERVER['REQUEST_URI'],"/") !== 0){
    $_SERVER['REQUEST_URI'] = "/".$_SERVER['REQUEST_URI'];
}

$app->run();
