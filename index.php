<?php

require __DIR__ . '/vendor/autoload.php';

$app = new \Silex\Application;
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), ['twig.path' => __DIR__ . '/tpl']);
$app->get('/', '\\Samwilson\\WikipediaReadability\\Controller::home');
$app->run();
