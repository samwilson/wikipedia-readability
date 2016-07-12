<?php

namespace Samwilson\WikipediaReadability;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

class Controller {

    /**
     * The main page.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function home(Request $request, Application $app) {
        $cat = $request->query->get('cat', false);
        $catPrefix = 'Category:';
        if (substr($cat, 0, strlen($catPrefix)) === $catPrefix) {
            $cat = substr($cat, strlen($catPrefix));
        }
        $tool = new Tool;
        $pages = $tool->search($cat);
        return $app['twig']->render('template.twig', [
                    'title' => 'Readability of pages in English Wikipedia',
                    'cat' => $cat,
                    'pages' => $pages,
        ]);
    }

}
