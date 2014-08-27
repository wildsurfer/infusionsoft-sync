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
        $tags = array(1, 2, 3);

        $c = new Contact($data, $tags);

        $this->assertEquals($data['Id'], $c->getId());
        $this->assertEquals($data['FirstName'], $c->field('FirstName'));
        $this->assertEquals($data['Email'], $c->field('Email'));
        $this->assertEquals($tags, $c->getTags());
    }

    /**
     * 'uniqueHash()' should serialize all data and return unique hash. It will
     * be used to verify if data changed or not
     */
    public function testUniqueHash()
    {
        $tags = array(1, 2, 3);
        $c = new Contact(array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com'
        ));
        $c1 = new Contact(
            array(
                'Id' => 1,
                'FirstName' => 'FirstName',
                'Email' => 'test@test.com'
            ),
            $tags
        );

        $h = $c->uniqueHash();
        $h1 = $c1->uniqueHash();

        $this->assertInternalType('string', $h);
        $this->assertEquals(32, strlen($h));
        $this->assertNotEquals($h1, $h);
    }

    /**
     * Contact object should be easily converted to array
     */
    public function testToArray()
    {
        $data = array(
            'Id' => 1,
            'FirstName' => 'FirstName',
            'Email' => 'test@test.com',
            'OwnerID' => 0
        );

        $tags = array(1, 2);

        $c = new Contact($data, $tags);

        $expected = $c->__toArray();

        unset($data['Id']);
        ksort($data);

        $this->assertEquals($expected, $data);
        $this->assertArrayNotHasKey('Id', $expected);
    }

    public function testSetGetTags()
    {
        $data = array('Id' => 1);

        $tags = array(1, 2);
        $c = new Contact($data);
        $c->setTags($tags);

        $this->assertEquals($tags, $c->getTags());
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
    public function testStatuses($status)
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

    /**
     * Each time status is set other statuses should be resetted
     */
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

    /**
     * Data should be parsed to match Infusionsoft transformations. For example
     * FirstName should be uppercased
     */
    public function testSetGetData()
    {
        $data = array(
            'FirstName' => 'test',
            'LastName' => 'test'
        );

        $dataExpected = array(
            'FirstName' => 'Test',
            'LastName' => 'Test',
            'OwnerID' => 0
        );

        $contact = new Contact();

        $contact->setData($data);

        $this->assertEquals($dataExpected, $contact->getData());
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
