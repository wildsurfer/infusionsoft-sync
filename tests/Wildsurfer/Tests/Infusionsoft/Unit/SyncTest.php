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

        $i = $contacts->count();
        $j = count($data);
        $this->assertEquals($i, $j);
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('createContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('createContact')
            ->will($this->returnValue($createdContact));

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->never())
            ->method('dsQuery');
        $i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $i->push($collection);

        $this->assertEquals(1, $expected['create']->count());
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('updateContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('updateContact')
            ->will($this->returnValue($updatedContact));

        $isdk = $this->getMockedIsdk();
        $i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $i->push($collection);

        $this->assertEquals(1, $expected['update']->count());
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('updateContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('updateContact')
            ->will($this->returnValue($skippedContact));

        $isdk = $this->getMockedIsdk();
        $i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $i->push($collection);

        $this->assertEquals(1, $expected['skip']->count());
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('createContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('createContact')
            ->will($this->returnValue($failedContact));

        $isdk = $this->getMockedIsdk();
        $i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $i->push($collection);

        $this->assertEquals(1, $expected['fail']->count());
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('updateContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('updateContact')
            ->will($this->returnValue($failedContact));

        $isdk = $this->getMockedIsdk();
        $i->setIsdk($isdk);

        $collection = new ContactCollection();
        $collection->create($data);

        $expected = $i->push($collection);

        $this->assertEquals(1, $expected['fail']->count());
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('createContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('createContact')
            ->will($this->returnValue(new Contact));

        $i->expects($this->never())
            ->method('updateContact');

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->never())
            ->method('dsQuery');

        $i->setIsdk($isdk);

        $i->push($collection);
    }


    /**
     * When we push one contact we don't need to load all contacts from IS.
     * This is handy when you know exactly that contact is new and didn't
     * present remotely.
     */
    public function testPushOneContactUpdate()
    {
        $contact = new Contact(array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));
        $collection = new ContactCollection();
        $collection->create($contact);

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('updateContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('updateContact')
            ->will($this->returnValue($contact));

        $i->expects($this->never())
            ->method('createContact');

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->never())
            ->method('dsQuery');

        $i->setIsdk($isdk);

        $i->push($collection);
    }

    /**
     * When contact is loaded `loadCon` should be triggered. This method should
     * return `Contact` object
     */
    public function testLoadContact()
    {
        $tags = array(1, 2);
        $infsftTags = array(
            array('GroupId' => 1),
            array('GroupId' => 2)
        );
        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('loadCon')
            ->will($this->returnValue($data));
        $isdk->expects($this->once())
            ->method('dsQuery')
            ->with(
                $this->equalTo('ContactGroupAssign'),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(array('GroupId'))
            )
            ->will($this->returnValue($infsftTags));

        $this->i->setIsdk($isdk);

        $expected = $this->i->loadContact(1);

        $this->assertInstanceOf('Wildsurfer\Infusionsoft\Contact', $expected);
        $this->assertEquals($expected->getTags(), $tags);
    }

    /**
     * If load failed we should catch exeption
     */
    public function testLoadContactFail()
    {
        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('loadCon')
            ->will($this->returnValue('ooops'));

        $this->i->setIsdk($isdk);

        $result = $this->i->loadContact(1);
        $this->assertInternalType('string', $result);
    }

    /**
     * If load tags failed we should catch exeption
     */
    public function testLoadContactTagsFail()
    {
        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('loadCon')
            ->will($this->returnValue(array()));
        $isdk->expects($this->once())
            ->method('dsQuery')
            ->will($this->returnValue('ooops'));

        $this->i->setIsdk($isdk);

        $result = $this->i->loadContact(1);
        $this->assertInternalType('string', $result);
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

        $isdk = $this->getMockedIsdk();
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
     * When 'tags' are present in data they should be correctly created
     */
    public function testCreateContactTags()
    {
        $contact = new Contact(
            array('Email' => 'test1@test.com'),
            array(1, 2)
        );

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->exactly(2))
            ->method('grpAssign')
            ->will($this->returnValue(true));

        $this->i->setIsdk($isdk);

        $expected = $this->i->createContact($contact);
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

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('addCon')
            ->will($this->returnValue('ooops'));

        $this->i->setIsdk($isdk);

        $result = $this->i->createContact($contact);
        $expected = $result->getErrorMessage();
        $this->assertNotEmpty($expected);
        $this->assertEquals(true, $result->isFailed());
    }

    /**
     * If adding tags failed we should remove created contact and return error
     */
    public function testCreateContactTagsFailed()
    {
        $contact = new Contact(
            array('Email' => 'test1@test.com'),
            array(1, 2)
        );

        $id = 1;

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('grpAssign')
            ->will($this->returnValue('ooops'));
        $isdk->expects($this->once())
            ->method('addCon')
            ->will($this->returnValue($id));
        $isdk->expects($this->once())
            ->method('dsDelete')
            ->with($this->equalTo('Contact'), $this->equalTo($id))
            ->will($this->returnValue(true));

        $this->i->setIsdk($isdk);

        $expected = $this->i->createContact($contact);

        $this->assertEquals(true, $expected->isFailed());
    }

    /**
     * When contact is updated `updateCon` and `loadCon` should be triggered.
     * `loadCon` is needed to check if contact was updated or not. This method
     * should return `Contact` object
     */
    public function testUpdateContact()
    {
        $dataOld = array(
            'Id' => 1,
            'Email' => 'test111@test.com',
            'FirstName' => 'FirstName1'
        );

        $data = array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        );

        $contactOld = new Contact($dataOld);
        $contact = new Contact($data);

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('loadContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('loadContact')
            ->will($this->returnValue($contactOld));

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('updateCon')
            ->will($this->returnValue(1));

        $i->setIsdk($isdk);

        $expected = $i->updateContact($contact);

        $this->assertInstanceOf('Wildsurfer\Infusionsoft\Contact', $expected);
        $this->assertEquals(true, $expected->isUpdated());
        $this->assertEquals($expected->field('Email'), $contact->field('Email'));
    }

    /**
     * If update failed we should catch exeption
     */
    public function testUpdateContactFail()
    {
        $dataOld = array(
            'Id' => 1,
            'Email' => 'test1231@test.com',
            'FirstName' => 'FirstName1'
        );
        $contactOld = new Contact($dataOld);
        $contact = new Contact(array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' => 'FirstName1'
        ));

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('loadContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('loadContact')
            ->will($this->returnValue($contactOld));

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('updateCon')
            ->will($this->returnValue('ooops'));

        $i->setIsdk($isdk);

        $result = $i->updateContact($contact);
        $expected = $result->getErrorMessage();
        $this->assertNotEmpty($expected);
        $this->assertEquals(true, $result->isFailed());
    }

    /**
     * When 'tags' are present in data they should be correctly created
     */
    public function testUpdateContactTags()
    {
        $oldData = array(
            'Id' => 1,
            'Email' => 'test1@test.com'
        );
        $oldTags = array(1, 3);
        $contactOld = new Contact($oldData, $oldTags);

        $contact = new Contact(
            array(
                'Id' => 1,
                'Email' => 'test1@test.com'
            ),
            array(1, 2)
        );

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('loadContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('loadContact')
            ->will($this->returnValue($contactOld));

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('grpAssign')
            ->will($this->returnValue(true));
        $isdk->expects($this->once())
            ->method('grpRemove')
            ->will($this->returnValue(true));

        $i->setIsdk($isdk);

        $expected = $i->updateContact($contact);
    }

    /**
     * If updating tags failed we should return error. Contact should be
     * reverted to original state
     */
    public function testUpdateContactTagsFailed()
    {
        $dataOld = array(
            'Id' => 1,
            'Email' => 'test11@test.com'
        );

        $contactOld = new Contact($dataOld);

        $contact = new Contact(
            array(
                'Id' => 1,
                'Email' => 'test1@test.com'
            ),
            array(1, 2)
        );

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('loadContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('loadContact')
            ->will($this->returnValue($contactOld));

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('grpAssign')
            ->will($this->returnValue('ooops'));
        $isdk->expects($this->at(0))
            ->method('updateCon')
            ->will($this->returnValue(1));
        $isdk->expects($this->at(2))
            ->method('updateCon')
            ->with($this->equalTo(1), $this->equalTo((array)$contactOld))
            ->will($this->returnValue(1));

        $i->setIsdk($isdk);

        $expected = $i->updateContact($contact);

        $this->assertEquals(true, $expected->isFailed());
        $this->assertEquals($contact->field('Email'), $expected->field('Email'));
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

        $i = $this->getMockBuilder('\Wildsurfer\Infusionsoft\Sync')
            ->disableOriginalConstructor()
            ->setMethods(array('loadContact'))
            ->getMock();

        $i->expects($this->once())
            ->method('loadContact')
            ->will($this->returnValue($contact));

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->never())
            ->method('updateCon');

        $i->setIsdk($isdk);

        $expected = $i->updateContact($contact);
        $this->assertEquals(true, $expected->isSkipped());
    }

    /**
     * This is a function to create mocked \Isdk object. Nothing interesting
     */
    protected function getMockedIsdk(array $response = array())
    {
        $isdk = $this->getMockBuilder('\iSDK')
            ->setMethods(array(
                'dsQuery',
                'dsDelete',
                'updateCon',
                'addCon',
                'loadCon',
                'grpAssign',
                'grpRemove'
            ))->getMock();

        if ($response) {
            $isdk->expects($this->once())
                ->method('dsQuery')
                ->will($this->returnValue($response));
        }

        return $isdk;
    }
}
