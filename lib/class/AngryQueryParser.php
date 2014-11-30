<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * AngryQueryParser is parser for easy needs
 * 
 * Version: 0.1.0
 * 
 * @author Vitaliy
 */
class AngryQueryParser {
    
    public $angryMode = false;
    public $proxy_list = 'AngryCurl-master/import/proxy_list.txt';
    public $user_agent_list = 'AngryCurl-master/import/useragent_list.txt';
    public $images_download_dir = false;
    public $path_to_images = false;
    public $files_download_dir = false;
    public $path_to_files = false;
    public $request_method = "GET";
    public $request_post_data = array();
    public $request_headers = array();
    public $request_curl_options = array();
    


    /**
     * 
     * @param boolean $angryMode if true you have to setup $proxy_list and $user_agent_list
     * @param str $images_download_dir 
     * @param str $files_download_dir
     * @param str $proxy_list path tp proxy_list file
     * @param str $user_agent_list path tp user agent list file
     */
    public function __construct($angryMode = false, $images_download_dir = false,  $files_download_dir = false, $proxy_list = NULL, $user_agent_list = NULL) {
        $this->angryMode = $angryMode;
        if( $angryMode )
        {
            $this->proxy_list = ( !empty($proxy_list) )? $proxy_list : $this->proxy_list;
            $this->user_agent_list = ( !empty($user_agent_list) )? $user_agent_list : $this->user_agent_list;
        }
        if( $images_download_dir ){
            $this->images_download_dir = $images_download_dir;
            $this->path_to_images = "/". basename($images_download_dir);

        }
        if( $files_download_dir ){
            $this->files_download_dir = $files_download_dir;
            $this->path_to_files = "/". basename($files_download_dir);
        }
  
    }
    
    /**
     * RollingCurl arguments can be configured through this method
     */
    public function requestOptions( $method = "GET", $post_data = array(), $headers = array(), $options = array() ){
        $this->request_method = $method;
        if( !empty($post_data) ) $this->request_post_data = $post_data;
        if( !empty($headers) ) $this->request_headers = $headers;
        if( !empty($options) ) $this->request_curl_options = $options;
    }

    /**
     * Parse Page
     * @param type $url
     * @param array $args array of arguments key = name of variable, value = node 
     * example array("page_title" => "title", "title" => ".product_description h1");
     * vaue can be "Html Dom Element" or array($start_wpapper, $end_block_wrapper, $node, $attr)
     * example array("top_menu_links" => array("div.#menu186id16 li a", "href", "<!-- Begin Menu Bar -->", "<!-- End Menu Bar-->") )
     * @return boolean
     */
    public function getPage($url,  array $args, $meta = false){
        if (empty($url)) return false;
        if( $this->angryMode ){
            $AC2->load_proxy_list($this->proxy_list , 200 );
            $AC2->load_useragent_list( $this->user_agent_list );
        }
        $method = ( !empty($this->request_method) ) ? $this->request_method : "GET";
        $post_data = ( !empty($this->request_post_data) ) ? $this->request_post_data : NULL;
        $headers = ( !empty($this->request_headers) ) ? $this->request_headers : NULL;
        $options = ( !empty($this->request_curl_options) ) ? $this->request_curl_options : NULL;
        
        $headers['parser'] = array();
        $arr = array();
        foreach ( $args as $name => $node){
            if(is_array($node) ){
                for( $i = 0; $i < count($node); $i++ ){
                    $arr["AQP_".$name][$i] = $node[$i];
                }
            }else{
                $arr["AQP_".$name] = $node;
            }
        }
        
        $headers['parser'] = serialize($arr);
        
        
        if( $meta ){ $headers['AQP_P_META'] = $meta;}
        if( $this->images_download_dir ){ $headers['AQP_DOWNLOAD_DIR'] = $this->images_download_dir; }
        if( $this->path_to_images ){ $headers['AQP_NEW_PATH'] = $this->path_to_images; }
        if( $this->files_download_dir ){ $headers['AQP_FILES_DOWNLOAD_DIR'] = $this->files_download_dir;}
        if( $this->path_to_files ){ $headers['AQP_FILES_NEW_PATH'] = $this->path_to_files; }
        
        $callback = array('AngryQueryParser', 'callback_getPage');
        $obj = 'AC_'.time();
        $$obj = new AngryCurl($callback);
        $request = new AngryCurlRequest($url, $method, $post_data, $headers, $options);
        $$obj->add($request);
        $$obj->execute(200);
        $result = $$obj->callbackReturn;
        unset($$obj);
        return $result;
    }
    
    static public function callback_getPage($response, $info, $request) {
        if($info['http_code']===200)
        {
            $page = array();
            $page["info"] = $info;
            $hostname = self::getHostname($info['url']);
            
            if ($request->headers['AQP_P_META']) {
                $meta = self::getMetaInfo($response);
                $page['meta_description'] = $meta['description'];
                $page['meta_keywords'] = $meta['keywords'];
            }
            $options = unserialize($request->headers["parser"]);

            foreach ($options as $name => $node){
                $image_path = "";
                if( !in_array($name, array("AQP_P_META", "AQP_DOWNLOAD_DIR", "AQP_NEW_PATH") ) ){
                    $name = str_replace("AQP_", "", $name);
                    /*
                     * saving html node
                     */
                    if(is_array($node) ){
                        if( !empty($node[2]) && !empty($node[3]) ){
                            $start = $node[2];
                            $end = $node[3];
                            $page[$name] = self::getTextBetweenStrings($start, $end, $response);
                        }else{
                            $page[$name] = $response;
                        }
                        
                        if( !empty($node[0]) ){
                            $dom_node = $node[0];
                            $attr = ( !empty($node[1]) )? $node[1] : false;
                            $page[$name] = self::pQuery($page[$name], $dom_node, $attr); 
                            
                            //if we want to download images
                            if( isset($page[$name]["src"]) ){
                                $img_p = self::saveImage($page[$name]["src"], $hostname, $request->headers["AQP_DOWNLOAD_DIR"] ); 
                                $page[$name] = str_replace($img_p['old_path'], $request->headers["AQP_NEW_PATH"].'/'.$img_p['filename'], $page[$name]);
                            }else if(is_array($page[$name]) && current(array_keys($page[$name])) == 0 ){
                                foreach ($page[$name] as $img){
                                    if( isset($img["src"]) ){
                                        $i = (!isset($i))? 0 : $i;
                                        $img_p[$i] = self::saveImage($img["src"], $hostname, $request->headers["AQP_DOWNLOAD_DIR"] ); 
                                        $page[$name][$i] = str_replace($img_p[$i]['old_path'], $request->headers["AQP_NEW_PATH"].'/'.$img_p[$i]['filename'], $page[$name][$i]);
                                        $i++;
                                    }
                                }
                            }
                        }  
                    }else{
                        $page[$name] = self::pQuery($response, $node);
                    }
                    
                    /*
                     * searching files and images in node and saving them with changing path
                     */
                    
                    $image_path = self::savePageImages($page[$name], "img", "src", $hostname, $request->headers["AQP_DOWNLOAD_DIR"] );
                    if( !empty($image_path) ){
                        foreach ($image_path as $file){
                            $page[$name] = str_replace($file['old_path'], $request->headers["AQP_NEW_PATH"].'/'.$file['filename'], $page[$name]);
                        }
                    }
                    /*
                     * searching files saving them with changing path
                     */
                    
                    $files_path = self::saveFiles($page[$name], "a", "href", $hostname, $request->headers["AQP_FILES_DOWNLOAD_DIR"] );
                    if( !empty($files_path) ){
                        foreach ($files_path as $file){
                            $page[$name] = str_replace($file['old_path'], $request->headers["AQP_FILES_NEW_PATH"].'/'.$file['filename'], $page[$name]);
                        }
                    } 
                }
            }
           return $page;
        }else{
            return false;
        }
    }
    
    
    static public function getHostname($url){
        $url_arr = parse_url($url);
        return $url_arr['scheme'] .'://'. $url_arr['host'];
    }

    /**
     * 
     * @param string $document - html responce document, or other text
     * @param string $node - document element node using jQuery syntax for example "div.wrap > a"
     * @param string $attr - node attribute for exaple "href" or "src"
     * @return type
     */
    static function pQuery($responce, $node, $attr = false){
        $result = array();
        $pQuery = phpQuery::newDocument($responce);

        $elements = $pQuery->find($node);
        $count_nodes = count($elements);
        $i = 0;
        foreach ($elements as $el) {
            $pq = pq($el); 
            if( $attr ){
                if( $count_nodes > 1 ){
                    $res_el = $pq->attr($attr);
                    $result[$i][$attr] = $res_el;
                    $result[$i]['text'] = $pq->htmlOuter();
                }else{
                    $res_el = $pq->attr($attr);
                    $result[$attr] = $res_el;
                    $result['text'] = $pq->htmlOuter();
                }
            }else{
                if( $count_nodes > 1 ){
                    $result[$i] = $pq->htmlOuter();
                }else{
                    $result = $pq->htmlOuter();
                }
               
            }
            $i++;
        }
        return $result;
    }
    
    static public function saveImage($url, $hostname, $where_to){
        $res = array();
        $filename = basename($url);
        $new_path = $where_to."/".$filename;
        file_put_contents($new_path, fopen(self::formatLink($url, $hostname), 'r') );
        $rez['old_path'] = $url;
        $rez['filename'] = $filename;
        return $rez;
    }

    static public function savePageImages($search, $node, $attr, $hostname, $download_dir){
        $rez = array();
        $files = self::pQuery($search, $node, $attr);
        if( !empty($files) ){
            if( !is_array($files) ){
                $rez[0] = self::saveImage($files[$attr], $hostname, $download_dir);
            }else{
                foreach ($files as $file){
                    $i = (!isset($i))? 0 : $i;
                    $rez[$i] = self::saveImage($file[$attr], $hostname, $download_dir);
                    $i++;
                }
            }

        }
        return $rez;
    }

    static public function saveFiles($search, $node, $attr, $hostname, $download_dir){
        $files = self::pQuery($search, $node, $attr);
        $rez = array();
        $host_arr = parse_url($hostname);
        $host = $host_arr['host'];
        if( !empty($files) ){
            $i = 0;
            foreach ($files as $file){
                $filename = basename($file[$attr]);
                if(  preg_match('/.*(pdf|doc|docx|ppt|zip|txt|xls).*/', $file[$attr]) ){
                    
                    if( preg_match('/^(http:\/\/)/', $file[$attr]) )
                    {
                        $url = parse_url($file[$attr]);
                        if( $url['host'] == $host ){
                            if( $file[$attr] != $hostname ){
                                $new_path = ( !empty($fn_arr[0]) )? '/'.$fn_arr[0] : '/';
                               
                                if( empty($new_path) ){
                                    $new_path = $download_dir."/".$filename;
                                }                   
                            }else{
                                $new_path = $download_dir."/".$filename;
                            }

                        }else{
                            $new_path = $file[$attr];
                        }
 
                    }else{
                        $new_path = $download_dir."/".$filename;
                    }
                    $old_path = $file[$attr];

                    file_put_contents($new_path, fopen(self::formatLink($file[$attr], $hostname), 'r') );
                    $rez[$i]['old_path'] = $old_path;
                    $rez[$i]['filename'] = $filename;
                    $i++;
                }

                }
            }
        return $rez;
    }  
    
        /**
     * 
     * @param str $url - url of link
     * @param str $hostname - site host
     * @return str
     */
    static public function formatLink($url, $hostname) {
        if( $url =="#" || $url == "javascript:void(0)" ) return $url;
        $url_arr = parse_url($url);
        if (empty($url_arr["host"])) {
            return $hostname . '/' . ltrim($url, '/');
        } else {
            return $url;
        }
    }
    
    static public function getMetaInfo($response){
           $document = phpQuery::newDocument($response);
           $meta = $document->find("meta");
           $m = array();
           $m['description'] = "";
           $m['keywords'] = "";
           foreach ($meta as $el) {
               $pq = pq($el); // Это аналог $ в jQuery
               $meta_name = $pq->attr('name');
               switch ($meta_name) {
                   case "description":
                       $m['description'] = $pq->attr('content');
                       break;
                   case "keywords":
                       $m['keywords']= $pq->attr('content');
                       break;
               }
           }
           return $m;
    }

    static public function checkDomain($link, $domain) {
        $url_arr = parse_url($link);
        $link_host = ltrim($url_arr['host'], 'www.');
        $domain = ltrim($domain, 'www.');
        if ($link_host == $domain)
            return true;
        else
            return false;
    }
    
    static public function getTextBetweenStrings($start, $end, $str) {
        $matches = array();
        $regex = "/({$start})(.*?)({$end})/si";
        preg_match($regex, $str, $matches);
        return $matches[2];
    }
      
}
