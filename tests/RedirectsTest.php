<?php
namespace URIRequest;

use PHPUnit\Framework\TestCase;
use URIRequest\Redirect;
use DBAL\Database;

class RedirectsTest extends TestCase{
    protected $db;
    protected $redirect;
    
    /**
     * @covers \URIRequest\Redirect::__construct
     */
    public function setUp() {
        $this->db = new Database($GLOBALS['hostname'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['database']);
        if(!$this->db->isConnected()) {
            $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/database/database_mysql.sql'));
        $this->db->query(file_get_contents(dirname(__FILE__).'/sample_data/data.sql'));
        $this->redirect = new Redirect($this->db);
    }
    
    public function tearDown() {
        $this->db = null;
        $this->redirect = null;
    }
    
    /**
     * @covers \URIRequest\Redirect::__construct
     * @covers \URIRequest\Redirect::setRedirectTable
     * @covers \URIRequest\Redirect::getRedirectTable
     */
    public function testChangeTableName() {
        $this->assertEquals('redirects', $this->redirect->getRedirectTable());
        $this->assertObjectHasAttribute('log_location', $this->redirect->setRedirectTable(false));
        $this->assertEquals('redirects', $this->redirect->getRedirectTable());
        $this->redirect->setRedirectTable(145);
        $this->assertEquals('redirects', $this->redirect->getRedirectTable());
        $this->redirect->setRedirectTable('my_redirect_table');
        $this->assertNotEquals('redirects', $this->redirect->getRedirectTable());
        $this->assertEquals('my_redirect_table', $this->redirect->getRedirectTable());
        $this->redirect->setRedirectTable('redirects');
    }
    
    /**
     * @covers \URIRequest\Redirect::__construct
     * @covers \URIRequest\Redirect::setLogLocation
     * @covers \URIRequest\Redirect::getLogLocation
     */
    public function testChangeLogLocation() {
        $this->assertFalse($this->redirect->getLogLocation());
        $this->assertObjectHasAttribute('log_location', $this->redirect->setLogLocation(dirname(dirname(__FILE__)).'/logs/redirect_request_errors.txt'));
        $this->assertNotFalse($this->redirect->getLogLocation());
    }
    
    /**
     * @covers \URIRequest\Redirect::__construct
     * @covers \URIRequest\Redirect::addRedirect
     * @covers \URIRequest\Redirect::checkURI
     * @covers \URIRequest\SafeURI::makeURLSafe
     */
    public function testAddRedirect() {
        // Test successfully adding
        $this->assertTrue($this->redirect->addRedirect('/my-new-redirect', '/my/new/redirect'));
        // Test adding a value that should already exist
        $this->assertFalse($this->redirect->addRedirect('/my-new-redirect', '/my/new/redirect'));
        // Test adding a value that is not a string
        $this->assertFalse($this->redirect->addRedirect('/test-fail', false));
    }
    
    /**
     * @covers \URIRequest\Redirect::__construct
     * @covers \URIRequest\Redirect::updateRedirect
     * @covers \URIRequest\Redirect::checkURI
     * @covers \URIRequest\Redirect::logRequest
     * @covers \URIRequest\SafeURI::makeURLSafe
     */
    public function testUpdateRedirect() {
        // Test successfully updating
        $this->assertTrue($this->redirect->updateRedirect('/hello-world', '/hello-world', '/new-location'));
        // Test updaing none existant URI
        $this->assertFalse($this->redirect->updateRedirect('/does_not_exist', '/does_not_exist', '/hello'));
        // Test updating with boolean rather than string
        $this->assertFalse($this->redirect->updateRedirect('/hello-world', false, '/home'));
        // Tes updating with number instead of string
        $this->assertFalse($this->redirect->updateRedirect('/hello-world', '/hello/world', 15));
    }
    
    /**
     * @covers \URIRequest\Redirect::__construct
     * @covers \URIRequest\Redirect::addRedirect
     * @covers \URIRequest\Redirect::deleteRedirect
     * @covers \URIRequest\Redirect::checkURI
     * @covers \URIRequest\Redirect::logRequest
     * @covers \URIRequest\SafeURI::makeURLSafe
     */
    public function testDeleteRedirect() {
        // Test successfull delete
        $this->assertTrue($this->redirect->deleteRedirect('/hello-world'));
        // Test deleting value that shouldn't exist or has already been deleted
        $this->assertFalse($this->redirect->deleteRedirect('/hello-world'));
        // Test deleting value that is none existant
        $this->assertFalse($this->redirect->deleteRedirect('/dsfsdfsdf'));
    }
    
    /**
     * @covers \URIRequest\Redirect::__construct
     * @covers \URIRequest\Redirect::checkURI
     * @covers \URIRequest\Redirect::logRequest
     * @covers \URIRequest\SafeURI::makeURLSafe
     */
    public function testCheckURIs() {
        $this->assertFalse($this->redirect->checkURI('/this-does-not-exist'));
        $this->assertEquals('https://www.google.co.uk', $this->redirect->checkURI('/google'));
        $this->assertNotEmpty($this->redirect->checkURI('/hippos'));
    }
}
