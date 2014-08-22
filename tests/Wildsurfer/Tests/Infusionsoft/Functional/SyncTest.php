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
        'appname' => 'ni158',
        'apikey' => 'cc93d26f22ef921bba8cf9cc2819675b',
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
        //$isdk = $this->i->getIsdk();
        //$contacts = $isdk->
        //foreach ($contacts as $contact) {
            //$app['isdk']->dsDelete('Contact', $contact['Id']);
        //}
    }

    public function testConnection()
    {
        $isdk = $this->i->getIsdk();
    }
}
