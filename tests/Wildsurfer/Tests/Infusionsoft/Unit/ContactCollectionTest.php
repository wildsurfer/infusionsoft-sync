<?php

namespace Wildsurfer\Tests\Infusionsoft\Unit;

use Wildsurfer\Infusionsoft\Contact;
use Wildsurfer\Infusionsoft\ContactCollection;

/**
 * ContactCollectionTest
 */
class ContactCollectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * `read()` function should return array of all contacts from stack. Keys are
     * hashes. For more info see `ContactTest.php` file comments
     */
    public function readTest()
    {
        $contact1 = array(
            'FirstName' => 'FirstName1',
            'Email' => 'test1@test.com'
        );
        $contact2 = array(
            'FirstName' => 'FirstName2',
            'Email' => 'test2@test.com'
        );
        $c = new ContactCollection();
        $c->create($contact1);
        $c->create($contact2);

        $expected  = $c->read();

        foreach ($expected as $k => $e) {
            // Key should be a string
            $this->assertInternalType('string', $key);
            // Value should be instance of `Contact` class
            $this->assertInstanceOf('Contact', $e);
        }

    }

    /**
     * `create()` function should add Contact to end of stack
     */
    public function createFromObjectTest()
    {
        $contact1 = new Contact(array(
            'FirstName' => 'FirstName1',
            'Email' => 'test1@test.com'
        ));
        $contact2 = new Contact(array(
            'FirstName' => 'FirstName2',
            'Email' => 'test2@test.com'
        ));
        $c = new ContactCollection();
        $c->create($contact1);
        $c->create($contact2);

        $expected  = $c->read();
        $this->assertCount(count($expected), 2);
    }

    /**
     * `create()` function should add Contact to end of stack. If array is given
     * it should be internally coonverted to Contact object
     */
    public function createFromArrayTest()
    {
        $contact1 = array(
            'FirstName' => 'FirstName1',
            'Email' => 'test1@test.com'
        );
        $contact2 = array(
            'FirstName' => 'FirstName2',
            'Email' => 'test2@test.com'
        );
        $c = new ContactCollection();
        $c->create($contact1);
        $c->create($contact2);

        $expected  = $c->read();
        $this->assertCount(count($expected), 2);
    }
}
