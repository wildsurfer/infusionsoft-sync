<?php

namespace Wildsurfer\Infusionsoft;

use iSDK;

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
        if (isset($this->options['tags']))
            return $this->options['tags'];
        else return array();
    }

    public function getConfigFields()
    {
        return $this->options['fields'];
    }

    public function pull()
    {
        $allContacts = new ContactCollection();

        $isdk = $this->getIsdk();
        $fields = $this->getConfigFields();
        if (!in_array('Groups', $fields))
            $fields[] = 'Groups';

        $validTags = $this->getConfigTags();

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
                    $tags = array();
                    if (!empty($r['Groups'])) {
                        $tags = array_intersect(explode(',', $r['Groups']), $validTags);
                        unset($r['Groups']);
                    }
                    $c = new Contact($r);
                    $c->setTags($tags);

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
        case 0:
            return $result;
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
            $response = $isdk->addCon($contact->getData());
            if (is_string($response)) {
                $messsage = 'Add failed. Error:' . $response;
                $contact->setErrorMessage($messsage);
                $contact->setIsFailed();
            } else {
                $contact->setId($response);
                $contact->setIsCreated();
                $tags = $contact->getTags();
                if (count($tags) > 0) {
                    foreach($tags as $t) {
                        $response1 = $isdk->grpAssign($contact->getId(), $t);
                        if (is_string($response1)) {
                            $messsage1 = 'Add Tag failed. Error:' . $response1;
                            $isdk->dsDelete('Contact', $contact->getId());
                            $contact->setErrorMessage($messsage1);
                            $contact->setIsFailed();
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $contact->setErrorMessage($e->getMessage());
            $contact->setIsFailed();
        }
        return $contact;
    }

    public function loadContact($id)
    {
        $isdk = $this->getIsdk();

        $fields =  $this->getConfigFields();
        if (!in_array('Groups', $fields))
            $fields[] = 'Groups';

        $contactData = $isdk->loadCon($id, $fields);
        if (!is_array($contactData))
            return 'Load failed. Error:' . $contactData;

        $validTags = array();
        if (!empty($contactData['Groups'])) {
            $contactTags = explode(',', $contactData['Groups']);
            $configTags = $this->getConfigTags();
            if ($configTags && $contactTags) {
                $validTags = array_intersect($contactTags, $configTags);
                sort($validTags);
            }
        }

        unset($contactData['Groups']);

        return new Contact($contactData, $validTags);
    }

    public function updateContact(Contact $contact)
    {
        $isdk = $this->getIsdk();

        try {
            $remote = $this->loadContact($contact->getId());

            if (is_string($remote)) {
                $contact->setErrorMessage($remote);
                $contact->setIsFailed();
            } else {
                if ($remote->uniqueHash() == $contact->uniqueHash()) {
                    $contact->setIsSkipped();
                } else {
                    $response = $isdk->updateCon($contact->getId(), $contact->getData());
                    if (is_string($response)) {
                        $messsage = 'Add failed. Error:' . $response;
                        $contact->setErrorMessage($messsage);
                        $contact->setIsFailed();
                    } else {
                        $contact->setId($response);
                        $contact->setIsUpdated();

                        $remoteTags = $remote->getTags();
                        $tags = $contact->getTags();

                        $diff = array_diff($tags, $remoteTags);
                        foreach ($diff as $d) {
                            $response1 = $isdk->grpAssign($contact->getId(), $d);
                            if (is_string($response1)) {
                                $messsage1 = 'Add Tag failed. Error:' . $response1;
                                $isdk->updateCon($contact->getId(), $remote->getData());
                                $contact->setErrorMessage($messsage1);
                                $contact->setIsFailed();
                                break;
                            }
                        }
                        $diffRemote = array_diff($remoteTags, $tags);
                        foreach ($diffRemote as $dr) {
                            $response2 = $isdk->grpRemove($contact->getId(), $dr);
                            if (is_string($response2)) {
                                $messsage1 = 'Delete Tag failed. Error:' . $response2;
                                $isdk->updateCon($contact->getId(), $remote->getData());
                                $contact->setErrorMessage($messsage2);
                                $contact->setIsFailed();
                                break;
                            }
                        }
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
        if (!$this->isdk) {
            $isdk = new iSDK();
            $isdk->cfgCon(
                $this->options['appname'],
                $this->options['apikey']
            );
            $this->setIsdk($isdk);
        }
        return $this->isdk;
    }
}
