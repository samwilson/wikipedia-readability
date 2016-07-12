<?php

namespace Samwilson\WikipediaReadability;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\FluentRequest;
use DaveChild\TextStatistics\TextStatistics;

/**
 * 
 */
class Tool {

    /** @var MediawikiApi */
    protected $api;

    public function __construct() {
        $apiUrl = 'https://en.wikipedia.org/w/api.php';
        $this->api = MediawikiApi::newFromApiEndpoint($apiUrl);
    }

    /**
     * Get an array of information about the pages in the given category.
     *
     * @param string $cat The category to search.
     * @return string[] The search results.
     */
    public function search($cat) {
        // Don't bother if no category provided.
        if (empty($cat)) {
            return [];
        }
        $pages = [];
        foreach ($this->categoryMembers($cat) as $page) {
            unset($page['ns']);
            $pages[$page['pageid']] = $page;
        }
        if (empty($pages)) {
            return [];
        }
        $textStatistics = new TextStatistics;
        $textStatistics->normalise = false;
        foreach ($this->firstParagraphs(array_keys($pages)) as $pageId => $extract) {
            $score = $textStatistics->fleschKincaidReadingEase($extract);
            $pages[$pageId]['score'] = $score;
            $pages[$pageId]['extract'] = $extract;
        }
        // Sort by score.
        usort($pages, function($a, $b) {
            return $a['score'] - $b['score'];
        });
        return $pages;
    }

    /**
     * Get the first 50 pages in the given category.
     *
     * @param string $cat The category to query.
     * @return string[] Information about the pages in the category (ID and title).
     */
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

    /**
     * Get the first paragraphs, as plain text, of a given set of pages.
     *
     * @param integer[] $pageIds The IDs of the pages to get paragraphs of.
     * @return string[] An array, keyed by page IDs and containing the first paragraphs.
     */
    public function firstParagraphs($pageIds) {
        // Query the extracts, in chunks of 20 (an API limit).
        $exLimit = 20;
        $pageIdsChunks = array_chunk($pageIds, $exLimit, true);
        $extracts = [];
        foreach ($pageIdsChunks as $pageIdsChunk) {
            $request = FluentRequest::factory()
                    ->setAction('query')
                    ->setParam('prop', 'extracts')
                    ->setParam('explaintext', true)
                    ->setParam('exintro', true)
                    ->setParam('exlimit', $exLimit)
                    ->setParam('pageids', join('|', $pageIdsChunk))
            ;
            $response = $this->api->getRequest($request);
            $extracts = array_merge($extracts, $response['query']['pages']);
        }

        // Assemble the extracts.
        $out = [];
        foreach ($extracts as $extract) {
            $ex = $extract['extract'];
            // Trim to only the first paragraph (unless there's only one paragraph).
            if (strpos($ex, "\n") !== false) {
                $ex = substr($ex, 0, strpos($ex, "\n"));
            }
            $out[$extract['pageid']] = $ex;
        }
        return $out;
    }

}
