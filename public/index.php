<?php

/**
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

// HTML title prefix
define('TITLE_PREFIX', ' | The DataTank');

// Website document root
define('UI_DOCROOT', __DIR__. '/../');

// Vendor directory
define('UI_VENDORPATH', realpath(__DIR__.'/../vendor/').DIRECTORY_SEPARATOR);

// Path to the local tdt-start folder
// TODO: improve
define("STARTPATH", __DIR__.'/../../../../');
// define("STARTPATH", __DIR__.'/../../start/');

define("BASE_URL", $this->hostname . $this->subdir);
$data['BASE_URL'] = BASE_URL;

//Register the Twig Service Provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => UI_DOCROOT.'views'
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

// Register the url generator service provider object
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// Must be included first
require_once 'authentication.php';

// If root is asked, redirect to the dataset management
$app->get('/ui{suffix}', function () use ($app) {
    return $app->redirect(BASE_URL . 'ui/datasets');
})->assert('suffix', '/?');

// The parameters that cannot be edited
$app['session']->set('notedible',array('generic_type' => 'generic_type',
                                        'resource_type' => 'resource_type',
                                        'columns' => 'columns',
                                        'column_aliases' => 'column_aliases'));


require_once 'packagesandresources.php';
require_once 'usermanagement.php';
require_once 'routemanagement.php';
require_once 'input.php';
// require_once 'choosefile.php';
// require_once 'generictypes.php';
// require_once 'puttingfile.php';
// require_once 'editpackagesandresources.php';
// require_once 'editresource.php';
// require_once 'input.php';
// require_once 'addjob.php';
require_once 'error.php';
// require_once 'puttingfileoptional.php';


// Make sure REQUEST_URI starts with a slash. This is needed for Silex to work properly.
if (strpos($_SERVER['REQUEST_URI'], "/") !== 0){
    $_SERVER['REQUEST_URI'] = "/" . $_SERVER['REQUEST_URI'];
}

$app->run();
