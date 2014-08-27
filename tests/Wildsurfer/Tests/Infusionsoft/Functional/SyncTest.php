<?php

namespace Wildsurfer\Tests\Infusionsoft\Functional;

use Wildsurfer\Infusionsoft\Sync;
use Wildsurfer\Infusionsoft\Contact;
use Wildsurfer\Infusionsoft\ContactCollection;

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
}
