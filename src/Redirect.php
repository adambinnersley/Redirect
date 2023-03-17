<?php
namespace URIRequest;

use DBAL\Database;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Redirect
{
    /**
     * This should be an instance of the database object
     * @var object
     */
    protected $db;
    
    /**
     * This should be the location where the redirect file is located
     * @var string
     */
    protected $file_location;

    /**
     * This needs to the the table name where the redirects are located
     * @var string
     */
    protected $redirect_table = 'redirects';
    
    /**
     * This should be the log location including filename
     * @var string
     */
    public $log_location;

    /**
     * Constructor
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Set the redirect table name
     * @param string $table This should be the name of the table that you want to look at for the redirects
     * @return $this
     */
    public function setRedirectTable($table)
    {
        if (!empty(trim($table)) && is_string($table)) {
            $this->redirect_table = $table;
        }
        return $this;
    }
    
    /**
     * Returns the table where the redirects are located
     * @return string
     */
    public function getRedirectTable()
    {
        return $this->redirect_table;
    }
    
    /**
     * Set the location of a redirect file including which should include an array
     * @param string $file_location This should be the file location
     * @return $this
     */
    public function setRedirectFile($file_location)
    {
        if (!empty(trim($file_location)) && is_string($file_location)) {
            $this->file_location = $file_location;
        }
        return $this;
    }
    
    /**
     * Returns the location of any redirect file if set
     * @return string|false If a file location is set will return a string else will return false
     */
    public function getRedirectFile()
    {
        if (!empty($this->file_location)) {
            return $this->file_location;
        }
        return false;
    }
    
    /**
     * Sets the log file location
     * @param string $location This should be the log location including filename
     * @return $this
     */
    public function setLogLocation($location)
    {
        if (!empty(trim($location)) && is_string($location)) {
            $this->log_location = $location;
        }
        return $this;
    }
    
    /**
     * If the log location is set will return the log location
     * @return string|false If the log location is set will return the location string else returns false
     */
    public function getLogLocation()
    {
        if (!empty($this->log_location)) {
            return $this->log_location;
        }
        return false;
    }
    
    /**
     * Checks to see if the given URI is listed in the redirects
     * @param string $uri This should be the URI you are checking for new locations
     * @param boolean $log If you want to log any requests that don't exist set this value to true else set to false for no logging
     * @return string|boolean If the given URI exists as a redirect a string will be returned else will return false
     */
    public function checkURI($uri, $log = true)
    {
        $check_db = $this->checkDBRedirects($uri);
        $file_check = $this->checkFileRedirects($uri);
        
        if ($check_db !== false) {
            return $check_db;
        } elseif ($file_check !== false) {
            return $file_check;
        }
        
        if ($log === true) {
            $this->logRequest($uri);
        }
        return false;
    }
    
    /**
     * Log any URI given which don't have a redirect
     * @param string $uri This should be the requested URI
     */
    private function logRequest($uri)
    {
        if ($this->getLogLocation() !== false) {
            $log = new Logger('requests');
            $log->pushHandler(new StreamHandler($this->getLogLocation(), Level::DEBUG));
            $log->info($uri);
        }
    }

    /**
     * Checks the database for any redirects
     * @param string $uri This should be the URI you are checking for any redirects
     * @return string|false If the given URI exist then will return the string else will return false
     */
    protected function checkDBRedirects($uri)
    {
        return $this->db->fetchColumn($this->getRedirectTable(), ['uri' => SafeURI::makeURLSafe($uri), 'active' => 1], ['redirect']);
    }
    
    /**
     * Checks a PHP file array if the redirect exists which can also include variables
     * @param string $url This should be the URI you are checking for any redirects
     * @return string|boolean
     */
    protected function checkFileRedirects($url)
    {
        if (file_exists($this->getRedirectFile())) {
            include($this->getRedirectFile());
            if (array_key_exists(SafeURI::makeURLSafe($url), $redirects)) {
                return $redirects[SafeURI::makeURLSafe($url)];
            }
        }
        return false;
    }
    
    /**
     * Add a new redirect to the database
     * @param string $uri This should be the URI that you wish to add a redirect for
     * @param string $redirect This should be the URI you want to redirect to
     * @param int $active If the redirect is active set to 1 else to disable set to 0
     * @return boolean If the redirect is successfully added will return true else returns false
     */
    public function addRedirect($uri, $redirect, $active = 1)
    {
        if ($uri !== $redirect && !empty(SafeURI::makeURLSafe($uri)) && !empty(SafeURI::makeURLSafe($redirect)) && !$this->checkDBRedirects($uri)) {
            if ($this->db->insert($this->getRedirectTable(), ['uri' => SafeURI::makeURLSafe($uri), 'redirect' => $this->checkRedirect($uri, $redirect), 'active' => intval($active)]) !== false) {
                $this->updateExistingRedirects($uri, $redirect);
                return true;
            }
        }
        return false;
    }
    
    /**
     * Delete a redirect from the database
     * @param string $uri This should be the URI that you wish to delete the redirect for
     * @return boolean If the redirect has successfully been deleted will return true else returns false
     */
    public function deleteRedirect($uri)
    {
        return $this->db->delete($this->getRedirectTable(), ['uri' => SafeURI::makeURLSafe($uri)]);
    }
    
    /**
     * Update a redirect and its information
     * @param string $uri This should be the current URI in the database
     * @param string $new_uri If the URI needs to be changed set this here else just set as the same as the $uri field
     * @param string $redirect This should be the new location if it needs to be change else just set to the current location
     * @param int $active If you want to activate the redirect set to 1 else to disable set to 0
     * @return boolean If the redirect has successfully been updated will return true else will return false
     */
    public function updateRedirect($uri, $new_uri, $redirect, $active = 1)
    {
        if ($new_uri !== $redirect && !empty(SafeURI::makeURLSafe($uri)) && !empty(SafeURI::makeURLSafe($new_uri)) && !empty(SafeURI::makeURLSafe($redirect))) {
            //$redirect = $this->checkRedirect($new_uri, $redirect)
            if ($this->db->update($this->getRedirectTable(), ['uri' => SafeURI::makeURLSafe($new_uri), 'redirect' => $redirect, 'active' => intval($active)], ['uri' => SafeURI::makeURLSafe($uri)], 1) !== false) {
                $this->updateExistingRedirects($uri, $redirect);
                return true;
            }
        }
        return false;
    }
    
    /**
     * Updates existing redirects to reduce number of redirects that may be carried out
     * @param string $uri The old redirect location
     * @param string $new_uri The new redirect location
     * @return booean If successfully updated will return true else will return false
     */
    public function updateExistingRedirects($uri, $new_uri)
    {
        return $this->db->update($this->getRedirectTable(), ['redirect' => SafeURI::makeURLSafe($new_uri)], ['redirect' => SafeURI::makeURLSafe($uri)]);
    }
    
    /**
     * Checks to make sure the redirect location isn't forwarding on to another location to reduce redirects
     * @param string $uri This should be the original URI
     * @param string $redirect This should be the redirect location
     * @return string The end location will be returned
     */
    private function checkRedirect($uri, $redirect)
    {
        $checkRedirect = $this->checkURI($redirect);
        if ($checkRedirect !== false && $this->checkURI($redirect) !== SafeURI::makeURLSafe($uri)) {
            return $checkRedirect;
        }
        return $redirect;
    }
}
