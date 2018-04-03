<?php

namespace URIRequest;

class SafeURI {
    
    /**
     * Remove any unsafe characters from the URI string
     * @param string $uri The original URI string
     * @return string The safe URI string should be returned
     */
    public static function makeURLSafe($uri) {
        if(is_string($uri)){
            return preg_replace('~[^-a-z0-9_/?]+~', '', strtolower(filter_var(trim($uri), FILTER_SANITIZE_URL)));
        }
        return '';
    }
    
    /**
     * Remove variables from a given string
     * @param string $uri This should be the URI or string you want to remove variables from
     * @param boolean $all If you want to remove all variables set to true
     * @param array $selected If you are only removing selected variables add them as an array item
     * @return string The string will be returned with the selected items removed
     */
    public static function removeVariables($uri, $all = true, $selected = array()) {
        if($all === true){
            return explode('?', $uri)[0];
        }
        else{
            $remove = array();
            $vars = array();
            parse_str($uri, $vars);
            foreach($selected as $item){
                $remove[] = '?'.$item.'='.$vars[$item];
                $remove[] = '&'.$item.'='.$vars[$item];
            }
            return str_replace($remove, '', $uri);
        }
        
    }
}
