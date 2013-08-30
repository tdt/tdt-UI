<?php

/**
 * Shows the list of users
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;

// Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

// Used to write json to file, formatted to be read by humans
require_once __DIR__.'/../src/nicejson-php/nicejson.php';

// Load users from file
// Fetch users from the auth.json file
$auth_config_file = STARTPATH."app/config/auth.json";

// This object will be used by user management and route management, so don't delete it
$userObject = json_decode(file_get_contents($auth_config_file));
$users = get_object_vars($userObject);

// Fetch routes from file
$routeFile = STARTPATH. "app/config/cores.json";
$routeObject = json_decode(Configurator::stripComments(file_get_contents($routeFile)));
$routes = get_object_vars($routeObject);

// Save the routes used per user
$userroutes = array();

// Loop through all cores
foreach ($routes as $namespace => $core) {
    // Loop through all routes within a core object
    foreach ($core->routes as $index => $route) {
        // Loop through all users within a route object
        foreach ($route->users as $user) {

            // Create array if empty
            if(!isset($userroutes[$user])){
                $userroutes[$user] = new \stdClass();
                $userroutes[$user]->routes = array();
            }

            $user_route = new \stdClass();
            $user_route->namespace = $namespace;
            $user_route->index = $index;


            array_push($userroutes[$user]->routes, $user_route);
        }
    }
}

// List users in auth.json
$app->get('/ui/users', function () use ($app, $userObject, $routes, $userroutes, $data) {
    $data['users'] = get_object_vars($userObject);
    $data['routes'] = $routes;
    $data['userroutes'] = $userroutes;

    $data['title'] = 'User management' . TITLE_PREFIX;

    return $app['twig']->render('users/list.twig',$data);
});

// Add, edit or remove a user
$app->match('/ui/users/edit/{account}', function (Request $request, $account = null) use ($app, $userObject, $auth_config_file, $userroutes, $routes, $routeFile, $routeObject, $data) {
    // Default = no write
    $write = false;

    //If the request comes from the userlist, a parameter account will be in the request
    $account = $request->get('account', null);

    // Check if the request is to remove the user
    if ($request->get("remove") != null){
        //Remove the user from the array
        unset($userObject->$account);

        // Remove routes for the user
        foreach ($routes as $core) {
            foreach ($core->routes as $index => $route) {
                // Look for the user in the user array of the current route
                $oldindex = array_search($account,$route->users);
                // If the user has access to the route, remove access
                if ($oldindex !== false){
                    unset($route->users[$oldindex]);
                }
            }
        }
        $write = true;
    }
    // Add or edit a user
    else{
        // If there is an account, provide edit
        if ($account != null){
            $olduser = $userObject->$account;

            //Make a list with the routes from the current user
            $routedefaults = array();

            if (!empty($userroutes[$account])){
                foreach ($userroutes[$account]->routes as $userroute) {

                    if(!isset($routedefaults[$userroute->namespace])){
                        $routedefaults[$userroute->namespace] = array();
                    }

                    array_push($routedefaults[$userroute->namespace],  $userroute->index);
                }
            }

            // Enter default data for the form
            $defaults = array(
                'function' => 'Edit',
                'account' => $account,
                'username' => $account,
                'documentation' => $olduser->documentation,
                'type' => $userObject->$account->type,
                'route_defaults' => $routedefaults
            );

            $data['button'] = "Edit";
        }
        // If there is no account, create a new one
        else{
            $defaults = array(
                'function' => 'Add',
            );
            $data['button'] = "Add";
        }
        $data = array_merge($data, $defaults);

        // Create the route checkboxes
        $data['routes'] = $routes;


        // If the method is POST, validate the form
        if ('POST' == $request->getMethod() && !isset($userObject->$account)) {

            // Retrieve the function (edit or add) and give it to Twig
            $data['button'] = $formdata['function'];

            // Fetch the correct old name of the user from the form
            $account = $formdata['account'];
            $newname = $formdata['username'];

            // Check if the username is already in use (if it has changed)
            if (strcmp($account, $newname) != 0 && isset($userObject->$newname)){
                // $form->get('username')->addError(new FormError('Username is already in use'));
            }
            else {
                // Check if the username has changed, if so, delete the account
                if (strcmp($account, $newname) != 0 && isset($userObject->$account)){
                    unset($userObject->$account);
                }

                // Edit user properties
                $userObject->$newname->type = $formdata['authenticationtype'];
                $userObject->$newname->documentation = $formdata['documentation'];
                $userObject->$newname->password = $formdata['password'];

                // Read route data
                $routedata = array();
                foreach ($formdata['routes'] as $element) {
                    $exploded = explode("//", $element);
                    $namespace = $exploded[count($exploded)-2];
                    if (!isset($routedata[$namespace])){
                        $routedata[$namespace] = array();
                    }
                    $routedata[$namespace][count($routedata[$namespace])] = $exploded[count($exploded)-1];
                }

                // Edit routes
                foreach ($routes as $namespace => $core) {
                    foreach ($core->routes as $index => $route) {
                        // Look for the user in the user array of the current route
                        $newindex = array_search($newname,$route->users);
                        $oldindex = array_search($account,$route->users);

                        // Route was checked
                        if (in_array($index, $routedata[$namespace])){
                            // If the username has changed, remove the access for the account first
                            if (strcmp($account, $newname) != 0 && $oldindex !== false){
                                unset($route->users[$oldindex]);
                            }
                            // If user is not allowed to a route yet, add him to it
                            if ($newindex === false){
                                $route->users[count($route->users)] = $newname;
                            }
                        }
                        // Route was not checked
                        else if ($newindex !== false){
                            unset($route->users[$newindex]);
                        }
                    }
                }
                $write = true;
            }
        }
    }

    // If a remove/edit/add is executed, we need to write to the config files
    if ($write){
        // Write to auth.json
        file_put_contents($auth_config_file, json_format($userObject));

        // Write to cores.json
        file_put_contents($routeFile, json_format($routeObject));

        // Redirect to the userlist
        return $app->redirect(BASE_URL . 'ui/users');
    }

    // Show the form
    else{
        // $data['form'] = $form->createView();
        $data['title'] = $data['button']." user";
        $data['header'] = $data['title'];

        $data['title'] = 'User management' . TITLE_PREFIX;

        // display the form
        return $app['twig']->render('users/form.twig', $data);
    }
})->value('account', null);