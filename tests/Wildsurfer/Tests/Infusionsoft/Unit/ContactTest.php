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
    public function testField()
    {
        $data = array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        );

        $c = new Contact($data);

        $this->assertEquals($data['Id'], $c->getId());
        $this->assertEquals($data['FirstName'], $c->field('FirstName'));
        $this->assertEquals($data['Email'], $c->field('Email'));
    }

    /**
     * 'uniqueHash()' should serialize all data and return unique hash. It will
     * be used to verify if data changed or not
     */
    public function testUniqueHash()
    {
        $c = new Contact(array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        ));

        $h = $c->uniqueHash();

        $this->assertInternalType('string', $h);
        $this->assertEquals(32, strlen($h));
    }

    /**
     * Contact object should be easily converted to array
     */
    public function testToArray()
    {
        $data = array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        );

        $c = new Contact($data);

        $expected = $c->__toArray();

        unset($data['Id']);
        ksort($data);

        $this->assertEquals($expected, $data);
        $this->assertArrayNotHasKey('Id', $expected);
    }

    /**
     * Error message should be stored inside contact object
     */
    public function testSetErrorMessage()
    {
        $data = array(
            'Id' => 1,
            'FirstName' => 'FirstName'
        );

        $c = new Contact($data);
        $c->setErrorMessage('test');
        $expected = $c->getErrorMessage();
        $this->assertEquals('test', $expected);
    }

    /**
     * @dataProvider statusProvider
     */
    public function statusesTest($status)
    {
        $contact = new Contact();
        $p = 'setIs'.$status;
        $n = 'is'.$status;
        $shouldBeFalse = $contact->$n();
        $contact->$p();
        $shouldBeTrue = $contact->$n();

        $this->assertTrue($shouldBeTrue);
        $this->assertFalse($shouldBeFalse);
    }

    public function testResetStatus()
    {
        $contact = new Contact();

        $statuses = $this->statusProvider();

        foreach ($statuses as $k => $s) {
            $p = 'setIs'.$s[0];
            $n = 'is'.$s[0];

            $shouldBeFalse = $contact->$n();
            $contact->$p();
            $shouldBeTrue = $contact->$n();

            $this->assertTrue($shouldBeTrue);
            $this->assertFalse($shouldBeFalse);

            foreach ($statuses as $kk => $ss) {
                if ($kk <> $k) {
                    $p1 = 'setIs'.$ss[0];
                    $n1 = 'is'.$ss[0];
                    $shouldBeFalse = $contact->$n1();
                    $this->assertFalse($shouldBeFalse);
                }
            }

        }

    }

    public function statusProvider()
    {
        return array(
            array('Created'),
            array('Updated'),
            array('Failed'),
            array('Skipped'),
        );
    }
}
