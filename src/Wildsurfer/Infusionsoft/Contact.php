<?php

namespace Wildsurfer\Infusionsoft;

use DateTime;

class Contact
{
    protected $data = array();
    protected $tags = array();
    protected $id = null;
    protected $errorMessage = '';

    protected $isCreated = false;
    protected $isUpdated = false;
    protected $isFailed = false;
    protected $isSkipped = false;

    public function __construct(array $data = array(), array $tags = array())
    {
        if (!empty($data['Id'])) {
            $this->setId($data['Id']);
            unset($data['Id']);
        }
        $this->setData($data);
        $this->setTags($tags);
    }

    public function setData(array $data)
    {
        ksort($data);


        foreach ($data as $key => $val) {

            switch ($key) {
            case 'FirstName':
            case 'LastName':
            case 'City':
            case 'City2':
            case 'City3':
            case 'StreetAddress1':
            case 'StreetAddress2':
            case 'Address2Street1':
            case 'Address2Street2':
            case 'Address3Street1':
            case 'Address3Street2':
                $data[$key] = ucfirst(substr($val, 0, 40));
            case 'Phone1':
            case 'Phone2':
            case 'Phone3':
            case 'Phone4':
            case 'Phone5':
                $phone = preg_replace('/[^0-9]/', '', $val);
                $length = strlen($phone);

                if ($length == 10) {
                    $data[$key] = sprintf('(%d) %d-%d',
                        substr($phone, 0, 3),
                        substr($phone, 3, 3),
                        substr($phone, 6, 4)
                    );
                }
                elseif ($length == 11) {
                    $data[$key] = sprintf('%d (%d) %d-%d',
                        substr($phone, 0, 1),
                        substr($phone, 1, 3),
                        substr($phone, 3, 3),
                        substr($phone, 6, 4)
                    );
                }

                break;
            case 'Birthday':
                if ($val instanceof DateTime)
                    $date = $val;
                else
                    $date = new DateTime($val);
                $data[$key] = $date->format('Ymd\TH:i:s');
                break;
            }

            if (empty($data['OwnerID']))
                $data['OwnerID'] = 0;
        }

        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setTags(array $tags)
    {
        sort($tags);
        $this->tags = $tags;
        return $this;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setId($id)
    {
        $this->id = (int)$id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function resetStatus()
    {
        $this->isCreated = false;
        $this->isUpdated = false;
        $this->isFailed = false;
        $this->isSkipped = false;
        return $this;
    }

    public function setIsCreated()
    {
        $this->resetStatus();
        $this->isCreated = true;
        return $this;
    }

    public function isCreated()
    {
        return $this->isCreated;
    }

    public function setIsUpdated()
    {
        $this->resetStatus();
        $this->isUpdated = true;
        return $this;
    }

    public function isUpdated()
    {
        return $this->isUpdated;
    }


    public function setIsFailed()
    {
        $this->resetStatus();
        $this->isFailed = true;
        return $this;
    }

    public function isFailed()
    {
        return $this->isFailed;
    }

    public function setIsSkipped()
    {
        $this->resetStatus();
        $this->isSkipped = true;
        return $this;
    }

    public function isSkipped()
    {
        return $this->isSkipped;
    }

    public function field($name)
    {
        if (empty($this->data[$name]))
            return null;
        else
            return $this->data[$name];
    }

    public function uniqueHash()
    {
        return md5(
            var_export($this->data, true).
            var_export($this->tags, true)
        );
    }

    public function setErrorMessage($msg)
    {
        $this->errorMessage = $msg;
        return $this;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function __toArray()
    {
        return $this->data;
    }

}
