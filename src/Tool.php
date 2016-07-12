<?php

namespace Samwilson\WikimediaReadability;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\FluentRequest;
use DaveChild\TextStatistics\TextStatistics;

class Tool {

    /** @var MediawikiApi */
    protected $api;

    public function __construct() {
        $apiUrl = 'https://en.wikipedia.org/w/api.php';
        $this->api = MediawikiApi::newFromApiEndpoint($apiUrl);
    }

    public function search($cat) {
        // Don't bother if no category provided.
        if (empty($cat)) {
            return [];
        }
        $catMembers = [];
        foreach ($this->categoryMembers($cat) as $page) {
            $catMembers[$page['pageid']] = $page;
        }
        if (empty($catMembers)) {
            return [];
        }
        $pagesInfo = [];
        $textStatistics = new TextStatistics;
        foreach ($this->firstParagraphs($catMembers) as $page) {
            $score = $textStatistics->fleschKincaidReadingEase($page['extract']);
            $page['score'] = $score;
            // Construct a key to sort by.
            $key = str_pad($score * 100, 5, 0, STR_PAD_LEFT) . $page['title'];
            $pagesInfo[$key] = $page;
        }
        ksort($pagesInfo);
        return $pagesInfo;
    }

    public function categoryMembers($cat) {
        $limit = 50;
        $request = FluentRequest::factory()
                ->setAction('query')
                ->setParam('list', 'categorymembers')
                ->setParam('cmtype', 'page')
                ->setParam('cmlimit', $limit)
                ->setParam('cmtitle', "Category:$cat");
        $cmembers = $this->api->getRequest($request);
        return $cmembers['query']['categorymembers'];
    }

    public function firstParagraphs($pages) {
        $exLimit = 20;
        // Split the page IDs into groups of 20.
        $chunks = array_chunk($pages, $exLimit, true);
        $extracts = [];
        foreach ($chunks as $chunk) {
            $request = FluentRequest::factory()
                    ->setAction('query')
                    ->setParam('prop', 'extracts')
                    ->setParam('explaintext', true)
                    ->setParam('exintro', true)
                    ->setParam('exlimit', $exLimit)
                    ->setParam('pageids', join('|', array_keys($chunk)))
            ;
            $response = $this->api->getRequest($request);
            $extracts = array_merge($extracts, $response['query']['pages']);
        }

        foreach ($extracts as $extract) {
            $ex = $extract['extract'];
            // Trim to only the first paragraph (unless there's only one).
            if (strpos($ex, "\n") !== false) {
                $ex = substr($ex, 0, strpos($ex, "\n"));
            }
            $pages[$extract['pageid']]['extract'] = $ex;
        }
        return $pages;
    }

}
