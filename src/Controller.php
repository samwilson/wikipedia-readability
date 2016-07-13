<?php

namespace Samwilson\WikipediaReadability;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

class Controller
{

    /**
     * The main page.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function home(Request $request, Application $app)
    {
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

    /**
     * Category name autocompletion data source. Done in this inefficient way
     * (of two requests, one to here and then one onward to Wikipedia) in order
     * to avoid cross-site request problems.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function catSearch(Request $request, Application $app)
    {
        $api = new \Mediawiki\Api\MediawikiApi("https://en.wikipedia.org/w/api.php");
        $catNamespaceId = 14;
        $apiRequest = \Mediawiki\Api\FluentRequest::factory()
                ->setAction('opensearch')
                ->setParam('format', 'json')
                ->setParam('formatversion', 2)
                ->setParam('search', $request->query->get('q'))
                ->setParam('namespace', $catNamespaceId)
                ->setParam('suggest', true);
        $searchResults = $api->getRequest($apiRequest);
        $out = [];
        if (isset($searchResults[1])) {
            foreach ($searchResults[1] as $res) {
                // Strip the 'category' prefix.
                $out[] = substr($res, strlen('Category:'));
            }
        }
        return new \Symfony\Component\HttpFoundation\JsonResponse($out);
    }
}
