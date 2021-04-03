<?php
/**
 * Created by PhpStorm.
 * User: shahriar
 * Date: 12/8/16
 * Time: 10:42 AM
 */

namespace Application\Helper;

use Application\helpers\CliColor;

class BaseCrawler
{

    protected $model;
    protected $cli_color;
    protected $http_prefix = 'http://marzook.dev/';
    protected $base_dl_folder = '/';
    protected $force_overwrite = true;
    protected $is_http_request = false;



    /**
     * Select elements from $html using the css $selector.
     * When $as_array is true elements and their children will
     * be converted to array's containing the following keys (defaults to true):
     *
     *  - name : element name
     *  - text : element text
     *  - children : array of children elements
     *  - attributes : attributes array
     *
     * Otherwise regular DOMElement's will be returned.
     */

    public function select_elements($selector, $html, $as_array = true)
    {
        $dom = new SelectorDOM($html);
        return $dom->select($selector, $as_array);
    }

    /**
     * Convert $elements to an array.
     */

    public function elements_to_array($elements)
    {
        $array = array();
        for ($i = 0, $length = $elements->length; $i < $length; ++$i)
            if ($elements->item($i)->nodeType == XML_ELEMENT_NODE)
                array_push($array, $this->element_to_array($elements->item($i)));
        return $array;
    }

    /**
     * Convert $element to an array.
     */

    public function element_to_array($element)
    {
        $array = array(
            'name' => $element->nodeName,
            'attributes' => array(),
            'text' => $element->textContent,
            'children' => $this->elements_to_array($element->childNodes)
        );
        if ($element->attributes->length)
            foreach ($element->attributes as $key => $attr)
                $array['attributes'][$key] = $attr->value;
        return $array;
    }

    /**
     * Convert $selector into an XPath string.
     */

    public function selector_to_xpath($selector)
    {
        // remove spaces around operators
        $selector = preg_replace('/\s*>\s*/', '>', $selector);
        $selector = preg_replace('/\s*~\s*/', '~', $selector);
        $selector = preg_replace('/\s*\+\s*/', '+', $selector);
        $selector = preg_replace('/\s*,\s*/', ',', $selector);
        $selectors = preg_split('/\s+(?![^\[]+\])/', $selector);

        foreach ($selectors as &$selector) {
            // ,
            $selector = preg_replace('/,/', '|descendant-or-self::', $selector);
            // input:checked, :disabled, etc.
            $selector = preg_replace('/(.+)?:(checked|disabled|required|autofocus)/', '\1[@\2="\2"]', $selector);
            // input:autocomplete, :autocomplete
            $selector = preg_replace('/(.+)?:(autocomplete)/', '\1[@\2="on"]', $selector);
            // input:button, input:submit, etc.
            $selector = preg_replace('/:(text|password|checkbox|radio|button|submit|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/', 'input[@type="\1"]', $selector);
            // foo[id]
            $selector = preg_replace('/(\w+)\[([_\w-]+[_\w\d-]*)\]/', '\1[@\2]', $selector);
            // [id]
            $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)\]/', '*[@\1]', $selector);
            // foo[id=foo]
            $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)=[\'"]?(.*?)[\'"]?\]/', '[@\1="\2"]', $selector);
            // [id=foo]
            $selector = preg_replace('/^\[/', '*[', $selector);
            // div#foo
            $selector = preg_replace('/([_\w-]+[_\w\d-]*)\#([_\w-]+[_\w\d-]*)/', '\1[@id="\2"]', $selector);
            // #foo
            $selector = preg_replace('/\#([_\w-]+[_\w\d-]*)/', '*[@id="\1"]', $selector);
            // div.foo
            $selector = preg_replace('/([_\w-]+[_\w\d-]*)\.([_\w-]+[_\w\d-]*)/', '\1[contains(concat(" ",@class," ")," \2 ")]', $selector);
            // .foo
            $selector = preg_replace('/\.([_\w-]+[_\w\d-]*)/', '*[contains(concat(" ",@class," ")," \1 ")]', $selector);
            // div:first-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):first-child/', '*/\1[position()=1]', $selector);
            // div:last-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):last-child/', '*/\1[position()=last()]', $selector);
            // :first-child
            $selector = str_replace(':first-child', '*/*[position()=1]', $selector);
            // :last-child
            $selector = str_replace(':last-child', '*/*[position()=last()]', $selector);
            // :nth-last-child
            $selector = preg_replace('/:nth-last-child\((\d+)\)/', '[position()=(last() - (\1 - 1))]', $selector);
            // div:nth-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):nth-child\((\d+)\)/', '*/*[position()=\2 and self::\1]', $selector);
            // :nth-child
            $selector = preg_replace('/:nth-child\((\d+)\)/', '*/*[position()=\1]', $selector);
            // :contains(Foo)
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):contains\((.*?)\)/', '\1[contains(string(.),"\2")]', $selector);
            // >
            $selector = preg_replace('/>/', '/', $selector);
            // ~
            $selector = preg_replace('/~/', '/following-sibling::', $selector);
            // +
            $selector = preg_replace('/\+([_\w-]+[_\w\d-]*)/', '/following-sibling::\1[position()=1]', $selector);
            $selector = str_replace(']*', ']', $selector);
            $selector = str_replace(']/*', ']', $selector);
        }

        // ' '
        $selector = implode('/descendant::', $selectors);
        $selector = 'descendant-or-self::' . $selector;
        // :scope
        $selector = preg_replace('/(((\|)?descendant-or-self::):scope)/', '.\3', $selector);
        // $element
        $sub_selectors = explode(',', $selector);

        foreach ($sub_selectors as $key => $sub_selector) {
            $parts = explode('$', $sub_selector);
            $sub_selector = array_shift($parts);

            if (count($parts) && preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)) {
                $results = $matches[0];
                $results[] = str_repeat('/..', count($results) - 2);
                $sub_selector .= implode('', $results);
            }

            $sub_selectors[$key] = $sub_selector;
        }

        $selector = implode(',', $sub_selectors);

        return $selector;
    }
    public static function get_content($address)
    {
//        echo "\ngetting content from " . $address . "\n";
        try {
            $curl = curl_init();
// Set some options - we are passing in a useragent too here
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $address,
                CURLOPT_USERAGENT => 'Codular Sample cURL Request'
            ));
// Send the request & save response to $resp
            $content = curl_exec($curl);
// Close request to clear up some resources
            curl_close($curl);
            return $content;
        } catch (\Exception $ex) {
//            echo "\n".$this->cli_color->getColoredString('Exception Occurred','red')." : ". $ex->getMessage();

        }
        return '';
    }

    public static function count_selector($selector, $html)
    {
        $count_element = count(BaseCrawler::select_elements($selector, $html));
        return $count_element;
    }

    public function download_file($from_url, $to, $overwrite = false)
    {
        if (!$overwrite && file_exists($to))
            return true;
        try {
            $content = $this->get_content($from_url, false);
            file_put_contents($to, $content);
        } catch (\Exception $ex) {
            echo "\n".$this->cli_color->getColoredString('Exception Occurred','red')." : ". $ex->getMessage();
            return false;
        }
        return true;
    }
    public function is_newer_version($old_version,$new_version){
        if(version_compare($old_version,$new_version)==-1)
            return true;
        return false;
    }
    public function has_source_conflict($app_id){
        if($this->model->get_source($app_id)=='crawler')
            return false;
        return true;
    }
    protected function filter_text($description)
    {
        return strip_tags(preg_replace('/[\]\[\{\}]/',' ',$description));
    }

    /**
     * remove empty space and replace underscore
     * change characters to lower case
     * @param $text
     * @param bool $remove_empty_space
     * @return mixed
     */
    protected function simple_filter($text, $remove_empty_space = false){
        if($remove_empty_space){
            $text = preg_replace('/[^A-Za-z0-9_]/', '', $text);
            $text = preg_replace('/\s+/', '_', $text);
        }
        $text = strtolower($text);
        return $text;
    }
    protected function mkdir_for_dl($directory){
        if(!file_exists($directory))
            mkdir($directory);
    }
    protected function get_html_address($app_id,$path)
    {
        return $this->http_prefix.$app_id.'/'.basename($path);
    }

    public function set_force_overwrite($force_over_write = true)
    {
        $this->force_overwrite = $force_over_write;
    }
    protected function is_in_blacklist($app_id)
    {
        $flow_status = Product::get_field($app_id,'flow_status','new');
        if($flow_status=='deleted'){
            return true;
        }
        return false;
    }

    protected function prun_tags_on_exist_app_in_crawl($app_id, &$item)
    {
        $item['price'] = $this->model->get_field($app_id,'price',0);
        $item['visited'] = $this->model->get_field($app_id,'visited',0);
        $item['likes'] = $this->model->get_field($app_id,'likes',0);
        $item['downloads'] = $this->model->get_field($app_id,'downloads',0);
        $item['sounds'] = $this->model->get_field($app_id,'sounds',0);
        $item['document'] = $this->model->get_field($app_id,'document',0);
        $item['videos'] = $this->model->get_field($app_id,'videos',0);
    }

    protected function is_eligible_for_download($data){

        $keys_must_check = ['name','description','id'];
        $taboo_words = ['sex','lesbian','gay','girl','kiss','lip','wallpaper','vpn','sock','suck','poker','dance'];
        foreach ($keys_must_check as $key){
            foreach ($taboo_words as $taboo_word){
                if(strpos(strtolower($data[$key]),$taboo_word)!==false){
                    return false;
                }
            }
        }
        return true;
    }

    public function p($message){
        if(!$this->is_http_request){
            echo $message;
        }
    }




}