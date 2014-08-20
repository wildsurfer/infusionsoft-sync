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
    public function testRead()
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
            $this->assertInternalType('string', $k);
            // Value should be instance of `Contact` class
            $this->assertInstanceOf('Wildsurfer\Infusionsoft\Contact', $e);
        }

    }

    /**
     * `count()` is for calculating total amount of contacts in collection
     */
    public function testCount()
    {
        $c = new ContactCollection();

        $expected  = $c->count();
        $this->assertEquals(0, $expected);

        $contact1 = array(
            'FirstName' => 'FirstName1',
            'Email' => 'test1@test.com'
        );
        $contact2 = array(
            'FirstName' => 'FirstName2',
            'Email' => 'test2@test.com'
        );
        $c->create($contact1);
        $c->create($contact2);

        $expected  = $c->count();
        $this->assertEquals(2, $expected);
    }

    /**
     * `create()` function should add Contact to end of stack
     */
    public function testCreateFromObject()
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

        $expected  = $c->count();
        $this->assertEquals($expected, 2);
    }

    /**
     * `create()` function should add Contact to end of stack. If array is given
     * it should be internally coonverted to Contact object
     */
    public function testCreateFromArray()
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

        $expected  = $c->count();
        $this->assertEquals($expected, 2);
    }
}
