<?php

namespace Wildsurfer\Tests\Infusionsoft\Unit;

/**
 * InfusionsoftSyncTest
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
        $this->i = new InfusionsoftSync($this->testConfig);
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
            'apikey' =>  $this->testConfig['apikey']
        );
        $i = new InfusionsoftSync($options);
        $expected = $i->getConfig();
        $this->assertEquals($expected, $options);
    }

    /**
     * You can specify array of tags (formerly `groups`) to be synced
     */
    public function testConfigTags()
    {
        $options = array(
            'tags' => $this->testConfig['tags']
        );
        $i = new InfusionsoftSync($options);
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
            'fields' => $this->testConfig['fields']
        );
        $i = new InfusionsoftSync($options);
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
        $this->assertInstanceOf('ContactCollection', $contacts);

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
            array(
                'Email' => 'test1@test.com',
                'FirstName' => 'FirstName1'
            ),
            array(
                'Email' => 'test2@test.com',
                'FirstName' => 'FirstName2'
            )
        );

        $collection = new ContactCollection();
        foreach ($data as $d) {
            $collection->create($d);
        }

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->exactly(2))
            ->method('dsAdd')
            ->will($this->returnValue(1));

        $this->i->setIsdk($isdk);

        $expected = $this->i->push($collection);
        $this->assertEquals(count($expected['create']), count($data));
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
            array(
                'Id' => 1,
                'Email' => 'test1@test.com',
                'FirstName' => 'FirstName1'
            ),
            array(
                'Id' => 2,
                'Email' => 'test2@test.com',
                'FirstName' => 'FirstName2'
            )
        );

        $collection = new ContactCollection();
        $collection->create(array(
            'Id' => 2,
            'Email' => 'test2@test.com',
            'FirstName' => 'FirstName2222222'
        ));

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->exactly(1))
            ->method('dsUpdate')
            ->will($this->returnValue(2));

        $this->i->setIsdk($isdk);

        $expected = $this->i->push($collection);
        $this->assertEquals(
            $expected['update'][0]->field('FirstName'), 'FirstName2222222'
        );
        $this->assertEquals($expected['update'][0]->field('Id'), 2);
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
            array(
                'Id' => 1,
                'Email' => 'test1@test.com',
                'FirstName' = 'FirstName1'
            )
        );

        $collection = new ContactCollection();
        $collection->create(array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' = 'FirstName1'
        ));

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->never())
            ->method('dsUpdate');
        $isdk->expects($this->never())
            ->method('dsAdd');

        $this->i->setIsdk($isdk);

        $expected = $this->i->push($collection);
        $this->assertEquals(count($expected['skip']), 1);
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
            'FirstName' = 'FirstName1'
        );

        $collection = new ContactCollection();
        $collection->create($data);

        $isdk = $this->getMockedIsdk();
        $isdk->expects($this->once())
            ->method('dsAdd')
            ->will($this->throwException(new \Exception()));

        $this->i->setIsdk($isdk);

        $expected = $this->i->push($collection);
        $this->assertEquals(count($expected['fail']), 1);
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
            'Email' => 'test1@test.com',
            'FirstName' = 'FirstName1'
        );

        $collection = new ContactCollection();
        $collection->create($data);

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->once())
            ->method('dsUpdate')
            ->will($this->throwException(new \Exception()));

        $this->i->setIsdk($isdk);

        $expected = $this->i->push($collection);
        $this->assertEquals(count($expected['fail']), 1);
    }

    /**
     * When we push one contact we don't need to load all contacts from IS.
     * This is handy when you know exactly that contact is new and didn't
     * present remotely.
     */
    public function testPushOneContact()
    {
        $collection = new ContactCollection();
        $collection->create(array(
            'Id' => 1,
            'Email' => 'test1@test.com',
            'FirstName' = 'FirstName1'
        ));

        $isdk = $this->getMockedIsdk($data);
        $isdk->expects($this->once())
            ->method('dsAdd')
            ->will($this->returnValue(1));
        $isdk->expects($this->once())
            ->method('dsLoad')
            ->will($this->returnValue(array('Id' => 1)));
        $isdk->expects($this->never())
            ->method('dsQuery');

        $this->i->setIsdk($isdk);

        $expected = $this->i->push($collection);
    }

    /**
     * This is a function to create mocked \Isdk object. Nothing interesting
     */
    protected function getMockedIsdk(array $response = array())
    {
        $isdk = $this->getMockBuilder('\Isdk')
            ->setMethods(array('dsQuery'))
            ->getMock();

        $isdk->expects($this->once())
            ->method('dsQuery')
            ->will($this->returnValue($response));

        return $isdk;
    }
}
