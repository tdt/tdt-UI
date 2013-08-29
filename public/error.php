<?php

/**
 * Handling the error
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

$app->match('/ui/error{url}', function () use ($app,$data) {
    $data['error'] = $app['session']->get('error');
    return $app['twig']->render('error.twig', $data);

})->value('url', '');