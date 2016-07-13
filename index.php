<?php

require __DIR__.'/vendor/autoload.php';

$app = new \Silex\Application;
$app['debug'] = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$app->register(new Silex\Provider\TwigServiceProvider(), ['twig.path' => __DIR__.'/tpl']);
$app->get('/', '\\Samwilson\\WikipediaReadability\\Controller::home')->bind('home');
$app->get('/search', '\\Samwilson\\WikipediaReadability\\Controller::catSearch')->bind('search');
$app->run();
