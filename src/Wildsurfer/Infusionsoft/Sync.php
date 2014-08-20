<?php

namespace Wildsurfer\Infusionsoft;

use Isdk;

class Sync
{
    protected $options;
    protected $isdk;

    public function __construct(array $options)
    {
        if (empty($options['appname']))
            throw new SyncException("'appname' not set!");
        elseif (empty($options['apikey']))
            throw new SyncException("'apikey' not set!");
        elseif (empty($options['fields']))
            throw new SyncException("'fields' array not set!");

        $this->setConfig($options);
    }

    public function setConfig(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getConfig()
    {
        return $this->options;
    }

    public function getConfigTags()
    {
        return $this->options['tags'];
    }

    public function getConfigFields()
    {
        return $this->options['fields'];
    }

    public function pull()
    {
        $allContacts = new ContactCollection();

        $isdk = $this->getIsdk();
        $fields = $this->options['fields'];

        $limit = 1000;
        $page = 0;

        while(true)
        {
            $results = $isdk->dsQuery(
                'Contact',
                $limit,
                $page,
                array('Id' => '%'),
                $fields
            );

            if (is_string($results))
                throw new SyncException($results);
            else if (is_array($results)) {
                foreach ($results as $r) {
                    $c = new Contact($r);
                    $allContacts->create($c);
                }
            }

            if(count($results) < $limit) break;
            $page++;
        }
        return $allContacts;
    }

    public function push(ContactCollection $collection)
    {
        $isdk = $this->getIsdk();

        $count = $collection->count();

        $result = array(
            'skip' => new ContactCollection(),
            'create' => new ContactCollection(),
            'update' => new ContactCollection(),
            'fail' => new ContactCollection()
        );

        $contacts = $collection->read();

        switch ($count) {
        case ($count <= 0):
            throw new SyncException('Trying to push empty collection!');
            break;
        /**
         * If Collection has 1 contact we don't want to pull all contacts from
         * remote API.
         */
        case 1:
            $contact = reset($contacts);

            $id = $contact->getId();
            if ($id) {
                // Contact Id is set. Most likely this is update
                $c = $this->updateContact($contact);
            } else {
                // Contact ID not set. Most likely this is new contact
                $c = $this->createContact($contact);
            }

                if ($c->isCreated())
                    $result['create']->create($c);
                elseif ($c->isUpdated())
                    $result['update']->create($c);
                elseif ($c->isSkipped())
                    $result['skip']->create($c);
                elseif ($c->isFailed())
                    $result['fail']->create($c);

            break;
        default:
            foreach ($contacts as $contact) {
                $id = $contact->getId();
                if ($id) {
                    // Contact Id is set. Most likely this is update
                    $c = $this->updateContact($contact);
                } else {
                    // Contact ID not set. Most likely this is new contact
                    $c = $this->createContact($contact);
                }

                if ($c->isCreated())
                    $result['create']->create($c);
                elseif ($c->isUpdated())
                    $result['update']->create($c);
                elseif ($c->isSkipped())
                    $result['skip']->create($c);
                elseif ($c->isFailed())
                    $result['fail']->create($c);
            }
        }

        return $result;
    }

    public function createContact(Contact $contact)
    {
        $isdk = $this->getIsdk();

        try {
            $response = $isdk->addCon((array)$contact);
            if (is_string($response)) {
                $messsage = 'Add failed. Error:' . $response;
                $contact->setErrorMessage($messsage);
                $contact->setIsFailed();
            } else {
                $contact->setId($response);
                $contact->setIsCreated();
            }
        } catch (Exception $e) {
            $contact->setErrorMessage();
            $contact->setIsFailed();
        }
        return $contact;
    }

    public function updateContact(Contact $contact)
    {
        $isdk = $this->getIsdk();

        try {
            $remoteData = $isdk->loadCon($contact->getId());

            if (!is_array($remoteData)) {
                $messsage = 'Load failed. Error:' . $remoteData;
                $contact->setErrorMessage($messsage);
                $contact->setIsFailed();
            } else {
                $remote = new Contact($remoteData);
                if ($remote->uniqueHash() == $contact->uniqueHash()) {
                    $contact->setIsSkipped();
                } else {
                    $response = $isdk->updateCon($contact->getId(), (array)$contact);
                    if (is_string($response)) {
                        $messsage = 'Add failed. Error:' . $response;
                        $contact->setErrorMessage($messsage);
                        $contact->setIsFailed();
                    } else {
                        $contact->setId($response);
                        $contact->setIsUpdated();
                    }
                }
            }
        } catch (Exception $e) {
            $contact->setErrorMessage($e->getMessage());
            $contact->setIsFailed();
        }
        return $contact;
    }

    public function setIsdk(Isdk $i)
    {
        $this->isdk = $i;
        return $this;
    }

    public function getIsdk()
    {
        return $this->isdk;
    }
}
