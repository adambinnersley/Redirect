<?php

namespace URIRequest;

class SafeURI {
    
    public static function makeURLSafe($uri) {
        if(is_string($uri)){
            return strtolower(filter_var(trim($uri), FILTER_SANITIZE_URL));
        }
        return '';
    }
    
    public static function removeVariables($uri) {
        
    }
}
