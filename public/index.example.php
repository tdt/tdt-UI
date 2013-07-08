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

// Set the environment for error reporting
define('ENVIRONMENT', 'development');


/**
 * Alright, here we go!
 *
 * -----------------
 * DANGER ZONE BELOW
 * -----------------
 */

if (defined('ENVIRONMENT'))
{
    switch (ENVIRONMENT)
    {
        case 'development':
            error_reporting(E_ALL);
            ini_set('display_errors', True);
            $app['debug'] = true;
        break;

        case 'testing':
        case 'production':
            error_reporting(0);
        break;

        default:
            exit('The application environment is not set correctly.');
    }
}


// Website document root
define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR);

// Application directory
define('APPPATH', realpath(__DIR__.'/../app/').DIRECTORY_SEPARATOR);

// Vendor directory
define('VENDORPATH', realpath(__DIR__.'/../vendor/').DIRECTORY_SEPARATOR);

// TODO: remove the lines below and use configuration instead

// Hostname of The DataTank installation (With trailing slash!)
define('HOSTNAME', "...");

// Path to the local tdt-start folder (With trailing slash!)
define("STARTPATH", "...");

//Register the Twig Service Provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => DOCROOT.'views'
));

// Register the Form Service Provider
$app->register(new Silex\Provider\FormServiceProvider());

// Register the Validator and Translation Service Providers
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

// If root is asked, redirect to the resource management
$app->get('/', function () use ($app) {
    return $app->redirect('/package');
});

//start with resources management
require_once 'packagesAndResources.php';
require_once 'usermanagement.php';
require_once 'routemanagement.php';

$app->run();