<?php

namespace Wildsurfer\Tests\Infusionsoft\Unit;

use Wildsurfer\Infusionsoft\Contact;

/**
 * ContactTest
 */
class ContactTest extends \PHPUnit_Framework_TestCase
{
    /**
     * 'field()' should take string as argument and return field value
     */
    public function fieldTest()
    {
        $data = array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        );

        $c = new Contact($data);

        $this->assertEquals($data['Id'], $c->field('Id'));
        $this->assertEquals($data['FirstName'], $c->field('FirstName'));
        $this->assertEquals($data['Email'], $c->field('Email'));
    }

    /**
     * 'uniqueHash()' should serialize all data and return unique hash. It will
     * be used to verify if data changed or not
     */
    public function uniqueHashTest()
    {
        $c = new Contact(array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        ));

        $h = $c->uniqueHash();

        $this->assertInternalType('string', $h);
        $this->assertCount(32, strlen($h));
    }

    /**
     * Contact object should be easily converted to array
     */
    public function toArrayTest()
    {
        $data = array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        );

        $c = new Contact($data);

        $expected = (array)$c;

        $this->assertEquals($expected, $data);
        $this->assertArrayNotHasKey('Id', $expected);
    }
}
