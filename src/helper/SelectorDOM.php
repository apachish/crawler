<?php
namespace  Application\Helper;
use Application\Helper\BaseCrawler;

define('SELECTOR_VERSION', '1.1.6');

/**
 * SelectorDOM.
 *
 * Persitant object for selecting elements.
 *
 *   $dom = new SelectorDOM($html);
 *   $links = $dom->select('a');
 *   $list_links = $dom->select('ul li a');
 *
 */

class SelectorDOM {
    public $xpath;
    private $base_crawler;

    public function __construct($data) {
        $this->base_crawler = new BaseCrawler();
        if ($data instanceof \DOMDocument) {
            $this->xpath = new \DOMXPath($data);
        } else {
            $dom = new \DOMDocument();
            @$dom->loadHTML($data);
            $this->xpath = new \DOMXPath($dom);
        }
    }

    public function select($selector, $as_array = true) {
        $elements = $this->xpath->evaluate($this->base_crawler->selector_to_xpath($selector));
        return $as_array ? $this->base_crawler->elements_to_array($elements) : $elements;
    }
}
