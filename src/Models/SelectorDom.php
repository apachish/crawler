<?php

namespace Dadsun\Crawler\Models;

use Dadsun\Crawler\Models\Crawler;
use Illuminate\Database\Eloquent\Model;
use DOMDocument;
use DOMXPath;

class SelectorDom extends Model
{
    public $xpath;
    private $base_crawler;


    public function SelectorDOM($data) {
        $this->base_crawler = new Crawler();
        if ($data instanceof DOMDocument) {
            $this->xpath = new DOMXPath($data);
        } else {
            $dom = new DOMDocument();
            @$dom->loadHTML($data);
            return $this->xpath = new DOMXPath($dom);
        }
    }

    public function select($selector, $as_array = true,$get_html=false) {
        $elements = $this->xpath->evaluate($this->base_crawler->selector_to_xpath($selector));
        return $as_array ? $this->base_crawler->elements_to_array($elements,$get_html) : $elements;
    }
}
