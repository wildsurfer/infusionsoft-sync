<?php

namespace Wildsurfer\Tests\Infusionsoft\Functional;

use Wildsurfer\Infusionsoft\Sync;
use Wildsurfer\Infusionsoft\Contact;
use Wildsurfer\Infusionsoft\ContactCollection;
use DateTime;

/**
 * SyncTest
 */
class SyncTest extends \PHPUnit_Framework_TestCase
{
    protected $testConfig = array(
        'appname' => 'ce203',
        'apikey' => 'a27c9e85da73ab6967eaaaf66bc12b24',
        'fields' => array(
            'Email',
            'FirstName',
            'LastName',
            'OwnerID'
        )

    );

    public function setUp()
    {
        $this->i = new Sync($this->testConfig);
    }

    public function testConnection()
    {
        $isdk = $this->i->getIsdk();
    }

    public function testPushCreate()
    {
        $unique = md5(microtime());
        $isdk = $this->i->getIsdk();
        $contact = new Contact(array(
            'Email' => $unique.'@test.com',
            'FirstName' => $unique,
            'OwnerID' => 0
        ));
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $this->i->push($collection);
        $read = $c['create']->read();
        $data = reset($read);

        $expected = $isdk->loadCon($data->getId(), $this->i->getConfigFields());

        $this->assertEquals($expected, $contact->getData());

        $isdk->dsDelete('Contact', $data->getId());
    }

    public function testPushCreateWithTags()
    {
        $unique = md5(microtime());
        $isdk = $this->i->getIsdk();

        $tId1 = $isdk->dsAdd('ContactGroup', array('GroupName' => '1'.$unique));
        $tId2 = $isdk->dsAdd('ContactGroup', array('GroupName' => '2'.$unique));
        $tId3 = $isdk->dsAdd('ContactGroup', array('GroupName' => '3'.$unique));

        $validTags = array(
            $tId1 => '1'.$unique,
            $tId2 => '2'.$unique,
            $tId3 => '3'.$unique
        );

        $config = $this->testConfig;
        $config['tags'] = array_keys($validTags);

        $i = new Sync($config);

        $isdk = $i->getIsdk();
        $contact = new Contact(array(
            'Email' => $unique.'@test.com',
            'FirstName' => $unique,
            'OwnerID' => 0
        ));
        $contact->setTags(array_keys($validTags));

        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $this->i->push($collection);
        $read = $c['create']->read();
        $data = reset($read);

        $expected = $isdk->loadCon($data->getId(), $this->i->getConfigFields());
        $this->assertEquals($expected, $contact->getData());

        $expectedTags = $isdk->dsQuery('ContactGroupAssign', 1000, 0, array(
            'ContactId' => $data->getId()
        ), array(
            'GroupId'
        ));

        $this->assertCount(3, $expectedTags);

        $isdk->dsDelete('Contact', $data->getId());
        $isdk->dsDelete('ContactGroup', $tId1);
        $isdk->dsDelete('ContactGroup', $tId2);
        $isdk->dsDelete('ContactGroup', $tId3);
    }

    public function testPushUpdate()
    {
        $unique = md5(microtime());
        $isdk = $this->i->getIsdk();
        $contact = new Contact(array(
            'Email' => $unique.'@test.com',
            'FirstName' => $unique,
            'OwnerID' => 0
        ));
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $this->i->push($collection);
        $read = $c['create']->read();
        $data = reset($read);

        $dataUpdate = $data->getData();
        $dataUpdate['FirstName'] = 'Changed';
        $dataUpdate['Id'] = $data->getId();

        $contactUpdate = new Contact($dataUpdate);
        $collection = new ContactCollection();
        $collection->create($contactUpdate);

        $c = $this->i->push($collection);
        $read = $c['update']->read();
        $data = reset($read);

        $expected = $isdk->loadCon($data->getId(), $this->i->getConfigFields());

        $this->assertEquals($expected, $contactUpdate->getData());

        $isdk->dsDelete('Contact', $data->getId());
    }

    public function testPushUpdateWithTags()
    {
        $unique = md5(microtime());
        $isdk = $this->i->getIsdk();

        $tId1 = $isdk->dsAdd('ContactGroup', array('GroupName' => '1'.$unique));
        $tId2 = $isdk->dsAdd('ContactGroup', array('GroupName' => '2'.$unique));
        $tId3 = $isdk->dsAdd('ContactGroup', array('GroupName' => '3'.$unique));

        $validTags = array(
            $tId1 => '1'.$unique,
            $tId2 => '2'.$unique,
            $tId3 => '3'.$unique
        );

        $config = $this->testConfig;
        $config['tags'] = array_keys($validTags);

        $i = new Sync($config);

        $contact = new Contact(array(
            'Email' => $unique.'@test.com',
            'FirstName' => $unique,
            'OwnerID' => 0
        ));

        $contact->setTags(array($tId1));

        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $data = reset($read);

        $dataUpdate = $data->getData();
        $dataUpdate['Id'] = $data->getId();

        $contactUpdate = new Contact($dataUpdate);
        $contactUpdate->setTags(array($tId2, $tId3));

        $collection = new ContactCollection();
        $collection->create($contactUpdate);

        $c = $i->push($collection);
        $read = $c['update']->read();
        $data = reset($read);

        $expectedTags = $isdk->dsQuery('ContactGroupAssign', 1000, 0, array(
            'ContactId' => $data->getId()
        ), array(
            'GroupId'
        ));

        $this->assertCount(2, $expectedTags);

        $isdk->dsDelete('Contact', $data->getId());
        $isdk->dsDelete('ContactGroup', $tId1);
        $isdk->dsDelete('ContactGroup', $tId2);
        $isdk->dsDelete('ContactGroup', $tId3);
    }

    /**
     * @dataProvider nameProvider
     */
    public function testContactFieldNames($name)
    {
        $data = array(
            'FirstName' => $name,
            'LastName' => $name,
            'OwnerID' => 0
        );

        $config = $this->testConfig;
        $config['fields'] = array_keys($data);
        $i = new Sync($config);
        $isdk = $i->getIsdk();
        $contact = new Contact($data);
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $readData = reset($read);

        $expected = $isdk->loadCon($readData->getId(), $i->getConfigFields());

        foreach ($data as $k => $v) {
            $fields = $contact->getData();
            $this->assertEquals($expected[$k], $fields[$k]);
        }

        $isdk->dsDelete('Contact', $readData->getId());
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testContactFieldPhones($phone, $type)
    {
        $data = array(
            'Phone1' => $phone,
            'Phone1Type' => $type,
            'Phone2' => $phone,
            'Phone2Type' => $type,
            'Phone3' => $phone,
            'Phone3Type' => $type,
            'Phone4' => $phone,
            'Phone4Type' => $type,
            'Phone5' => $phone,
            'Phone5Type' => $type,
            'OwnerID' => 0
        );

        $config = $this->testConfig;
        $config['fields'] = array_keys($data);
        $i = new Sync($config);
        $isdk = $i->getIsdk();
        $contact = new Contact($data);
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $readData = reset($read);

        $expected = $isdk->loadCon($readData->getId(), $i->getConfigFields());

        foreach ($data as $k => $v) {
            $fields = $contact->getData();
            $this->assertEquals($expected[$k], $fields[$k]);
        }

        $isdk->dsDelete('Contact', $readData->getId());
    }

    /**
     * @dataProvider addressProvider
     */
    public function testContactFieldAddresses($type, $address1, $address2, $city, $state, $zip)
    {
        $data = array(
            'Address1Type' => $type,
            'StreetAddress1' => $address1,
            'StreetAddress2' => $address2,
            'City' => $city,
            'State' => $state,
            'ZipFour1' => $zip,
            'Address2Type' => $type,
            'Address2Street1' => $address1,
            'Address2Street2' => $address2,
            'City2' => $city,
            'State3' => $state,
            'ZipFour2' => $zip,
            'Address3Type' => $type,
            'Address3Street1' => $address1,
            'Address3Street2' => $address2,
            'City3' => $city,
            'State3' => $state,
            'ZipFour3' => $zip,
            'OwnerID' => 0
        );

        $config = $this->testConfig;
        $config['fields'] = array_keys($data);
        $i = new Sync($config);
        $isdk = $i->getIsdk();
        $contact = new Contact($data);
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $readData = reset($read);

        $expected = $isdk->loadCon($readData->getId(), $i->getConfigFields());

        foreach ($data as $k => $v) {
            $fields = $contact->getData();
            $this->assertEquals($expected[$k], $fields[$k]);
        }

        $isdk->dsDelete('Contact', $readData->getId());
    }

    /**
     * @dataProvider datesProvider
     */
    public function testContactFieldDates($date)
    {
        $data = array(
            'Birthday' => $date,
            'OwnerID' => 0
        );

        $config = $this->testConfig;
        $config['fields'] = array_keys($data);
        $i = new Sync($config);
        $isdk = $i->getIsdk();
        $contact = new Contact($data);
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $readData = reset($read);

        $expected = $isdk->loadCon($readData->getId(), $i->getConfigFields());

        foreach ($data as $k => $v) {
            $fields = $contact->getData();
            $this->assertEquals($expected[$k], $fields[$k]);
        }

        $isdk->dsDelete('Contact', $readData->getId());
    }

    /**
     * @dataProvider emailProvider
     */
    public function testContactFieldEmails($email)
    {
        $data = array(
            'Email' => $email,
            'OwnerID' => 0
        );

        $config = $this->testConfig;
        $config['fields'] = array_keys($data);
        $i = new Sync($config);
        $isdk = $i->getIsdk();
        $contact = new Contact($data);
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $readData = reset($read);

        $expected = $isdk->loadCon($readData->getId(), $i->getConfigFields());

        foreach ($data as $k => $v) {
            $fields = $contact->getData();
            $this->assertEquals($expected[$k], $fields[$k]);
        }

        $isdk->dsDelete('Contact', $readData->getId());
    }

    public function testContactFieldOwnerID()
    {
        $data = array(
            'OwnerID' => 1,
        );

        $config = $this->testConfig;
        $config['fields'] = array_keys($data);
        $i = new Sync($config);
        $isdk = $i->getIsdk();
        $contact = new Contact($data);
        $collection = new ContactCollection();
        $collection->create($contact);

        $c = $i->push($collection);
        $read = $c['create']->read();
        $readData = reset($read);

        $expected = $isdk->loadCon($readData->getId(), $i->getConfigFields());

        foreach ($data as $k => $v) {
            $fields = $contact->getData();
            $this->assertEquals($expected[$k], $fields[$k]);
        }

        $isdk->dsDelete('Contact', $readData->getId());
    }

    public function nameProvider()
    {
        return array(
            array('name'),
            array('Name'),
            array('Some Name'),
            array('some Name'),
            array('VeryLongNameMuchLongerThanInfusionsoftCanHandleAndSave')
        );
    }

    public function emailProvider()
    {
        return array(
            array('some@email.com'),
            array('sOmE@email.com'),
            array('someemail_1@test.com'),
            array('#$#^%876@7656.3')
        );
    }

    public function phoneProvider()
    {
        return array(
            array('12345', 'mobile'),
            array('1234567890', 'home'),
            array('12345678901', 'work'),
            array('(123) 456-7890', 'test'),
            array('(123)456-7890', 'mobile'),
            array('+1 (123) 456-7890', 'work'),
            array('+1(123)4567890', 'home')
        );
    }

    public function addressProvider()
    {
        return array(
            array('type', 'address1', 'address2', 'city', 'state', 'zip'),
            array('home', 'Address1', 'address2 123/e', 'City1', 'IL', 1),
            array('work', 'Address #_1', 'adEess2 123/e', 'City1 S#', 'IL1', 3213),
        );
    }

    public function datesProvider()
    {
        return array(
            array('11-11-1983'),
            array('1983-11-11'),
            array('1983-01-12'),
            array('1983-12-01'),
            array('11/11/1983'),
            array('1983/11/11'),
            array('1983/01/12'),
            array('1983/12/01'),
            array(new DateTime())
        );
    }
}
