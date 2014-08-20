<?php

namespace Wildsurfer\Tests\Infusionsoft\Unit;

use Wildsurfer\Infusionsoft\Sync;
use Wildsurfer\Infusionsoft\Contact;
use Wildsurfer\Infusionsoft\ContactCollection;

/**
 * SyncTest
 */
class SyncTest extends \PHPUnit_Framework_TestCase
{
    protected $testConfig = array(
        'appname' => 'myappname',
        'apikey' => 'asdU234YGUYG1234gjhg',
        'tags' => array(
            '1',
            '2',
            '3'
        ),
        'fields' => array(
            'Email',
            'FirstName',
            'LastName'
        )

    );

    public function setUp()
    {
        $this->i = new Sync($this->testConfig);
    }

    /**
     * Config params `appname` and `apikey` are requred to create calls to
     * Infusionsoft API. You can get them here:
     * http://ug.infusionsoft.com/article/AA-00442/0
     */
    public function testConfig()
    {
        $options = array(
            'appname' => $this->testConfig['appname'],
            'apikey' =>  $this->testConfig['apikey'],
            'fields' =>  $this->testConfig['fields']
        );
        $i = new Sync($options);
        $expected = $i->getConfig();
        $this->assertEquals($expected, $options);
    }

    /**
     * You can specify array of tags (formerly `groups`) to be synced
     */
    public function testConfigTags()
    {
        $options = array(
            'appname' => $this->testConfig['appname'],
            'apikey' =>  $this->testConfig['apikey'],
            'fields' =>  $this->testConfig['fields'],
            'tags' => $this->testConfig['tags']
        );
        $i = new Sync($options);
        $expected = $i->getConfigTags();
        $this->assertEquals($expected, $options['tags']);
    }

    /**
     * You will need to specify all contact fields that you want to perate with.
     * Please note that custom fields shpuld be prefixed with underscore.
     */
    public function testConfigFields()
    {
        $options = array(
            'appname' => $this->testConfig['appname'],
            'apikey' =>  $this->testConfig['apikey'],
            'fields' => $this->testConfig['fields']
        );
        $i = new Sync($options);
        $expected = $i->getConfigFields();
        $this->assertEquals($expected, $options['fields']);
    }

    /**
     * `pull()` function should return a ContactCollection object. Fore more
     * info read `ContactCollectionTest.php` file comments.
     */
    public function testPullContacts()
    {
        $data = array(
            array('Email' => 'test1@test.com'),
            array('Email' => 'test2@test.com')
        );
        $isdk = $this->getMockedIsdk($data);
        $this->i->setIsdk($isdk);

        $contacts = $this->i->pull();
        $this->assertInstanceOf('Wildsurfer\Infusionsoft\ContactCollection', $contacts);

        $read = $contacts->read();

        $this->assertCount(count($data), count($read));
    }

    /**
     * `push()` function is taking a `ContactCollection` object as argument. If
     * contact doesn't exist it will be created. Result will be array:
     *
     * array['created'] = ContactCollection();
     *
     */
    public function testPushContactsCreate()
    {
        $data = array(
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $createdContact = new Contact();
        $createdContact->setIsCreated();

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactCreate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactCreate')
            ->will($this->returnValue($createdContact));

        $isdk = $this->getMockedIsdk($data);
        $this->i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $this->i->push($collection);

        $this->assertCount(1, $expected['create']->count());
    }

    /**
     * `push()` function is taking a `ContactCollection` object as argument. If
     * contact was modified it will be updated. Result will be array:
     *
     * array['update'] = ContactCollection();
     *
     */
    public function testPushContactsUpdate()
    {
        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $updatedContact = new Contact();
        $updatedContact->setIsUpdated();

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactUpdate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactUpdate')
            ->will($this->returnValue($updatedContact));

        $isdk = $this->getMockedIsdk($data);
        $this->i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $this->i->push($collection);

        $this->assertCount(1, $expected['update']->count());
    }

    /**
     * `push()` function is taking a `ContactCollection` object as argument. If
     * contact was NOT modified it will NOT be updated or created. Result will
     * be array:
     *
     * array['skip'] = ContactCollection();
     *
     */
    public function testPushContactsSkip()
    {
        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $skippedContact = new Contact();
        $skippedContact->setIsSkipped();

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactUpdate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactUpdate')
            ->will($this->returnValue($skippedContact));

        $isdk = $this->getMockedIsdk($data);
        $this->i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $this->i->push($collection);

        $this->assertCount(1, $expected['skip']->count());
    }


    /**
     * `push()` function is taking a `ContactCollection` object as argument. If
     * contact creation failed exception will be catched. Result will be array:
     *
     * array['fail'] = ContactCollection();
     *
     */
    public function testPushContactsCreateFail()
    {
        $data = array(
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $failedContact = new Contact();
        $failedContact->setIsFailed();

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactCreate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactCreate')
            ->will($this->returnValue($failedContact));

        $isdk = $this->getMockedIsdk($data);
        $this->i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $this->i->push($collection);

        $this->assertCount(1, $expected['fail']->count());
    }

    /**
     * `push()` function is taking a `ContactCollection` object as argument. If
     * contact update failed exception will be catched. Result will be array:
     *
     * array['fail'] = ContactCollection();
     *
     */
    public function testPushContactsUpdateFail()
    {
        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $failedContact = new Contact();
        $failedContact->setIsFailed();

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactUpdate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactUpdate')
            ->will($this->returnValue($failedContact));

        $isdk = $this->getMockedIsdk($data);
        $this->i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $this->i->push($collection);

        $this->assertCount(1, $expected['fail']->count());
    }

    /**
     * When we push one contact we don't need to load all contacts from IS.
     * This is handy when you know exactly that contact is new and didn't
     * present remotely.
     */
    public function testPushOneContactCreate()
    {
        $collection = new ContactCollection();
        $collection->create(array(
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactCreate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactCreate');

        $i->expects($this->never())
            ->method('contactUpdate');

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->never())
            ->method('dsQuery');

        $this->i->setIsdk($isdk);

        $this->i->push($collection);
    }


    /**
     * When we push one contact we don't need to load all contacts from IS.
     * This is handy when you know exactly that contact is new and didn't
     * present remotely.
     */
    public function testPushOneContactUpdate()
    {
        $collection = new ContactCollection();
        $collection->create(array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\ContactCollection')
            ->setMethods(array('contactUpdate'))
            ->getMock();

        $i->expects($this->once())
            ->method('contactUpdate');

        $i->expects($this->never())
            ->method('contactCreate');

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->never())
            ->method('dsQuery');

        $this->i->setIsdk($isdk);

        $this->i->push($collection);
    }


    /**
     * When contact is created `addCon` should be triggered. This method should
     * return `Contact` object
     */
    public function testCreateContact()
    {
        $contact = new Contact(array(
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->once())
            ->method('addCon')
            ->will($this->returnValue(1));
        $isdk->expects($this->never())
            ->method('updateCon');
        $isdk->expects($this->never())
            ->method('loadCon');
        $isdk->expects($this->never())
            ->method('dsQuery');

        $this->i->setIsdk($isdk);

        $expected = $this->i->createContact($contact);

        $this->assertInstanceOf('Wildsurfer\Infusionsoft\Contact', $expected);
        $this->assertEquals(true, $expected->isCreated());
    }


    /**
     * If create failed we should catch exeption
     */
    public function testCreateContactFail()
    {
        $contact = new Contact(array(
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->once())
            ->method('addCon')
            ->will($this->throwException(new \Exception('ooops')));

        $this->i->setIsdk($isdk);

        $result = $this->i->createContact($contact);
        $expected = $result->getErrorMessage();
        $this->assertNotEmpty($expected);
        $this->assertEquals(true, $expected->isFailed());
    }

    /**
     * When contact is updated `updateCon` and `loadCon` should be triggered.
     * `loadCon` is needed to check if contact was updated or not. This method
     * should return `Contact` object
     */
    public function testUpdateContact()
    {
        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );
        $contact = new Contact($data);

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->once())
            ->method('updateCon')
            ->will($this->returnValue(1));
        $isdk->expects($this->once())
            ->method('loadCon')
            ->will($this->returnValue($data));
        $isdk->expects($this->never())
            ->method('addCon');
        $isdk->expects($this->never())
            ->method('dsQuery');

        $this->i->setIsdk($isdk);

        $expected = $this->i->updateContact($contact);

        $this->assertInstanceOf('Wildsurfer\Infusionsoft\ContactCollectionContact', $expected);
        $this->assertEquals(true, $expected->isUpdated());
    }

    /**
     * If update failed we should catch exeption
     */
    public function testUpdateContactFail()
    {
        $contact = new Contact(array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->once())
            ->method('updateCon')
            ->will($this->throwException(new \Exception('ooops')));

        $this->i->setIsdk($isdk);

        $result = $this->i->updateContact($contact);
        $expected = $result->getErrorMessage();
        $this->assertNotEmpty($expected);
        $this->assertEquals(true, $expected->isFailed());
    }

    /**
     * If contact was not changed we should skip the update and inform inquirer
     */
    public function testUpdateContactSkip()
    {
        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );
        $contact = new Contact($data);

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->never())
            ->method('updateCon');
        $isdk->expects($this->once())
            ->method('loadCon')
            ->will($this->returnValue($data));

        $this->i->setIsdk($isdk);

        $expected = $this->i->updateContact($contact);
        $this->assertEquals(true, $expected->isSkipped());
    }

    /**
     * This is a function to create mocked \Isdk object. Nothing interesting
     */
    protected function getMockedIsdk(array $response = array())
    {
        $isdk = $this->getMockBuilder('\Isdk')
            ->setMethods(array('dsQuery', 'updateCon', 'addCon', 'loadCon'))
            ->getMock();

        $isdk->expects($this->once())
            ->method('dsQuery')
            ->will($this->returnValue($response));

        return $isdk;
    }
}
