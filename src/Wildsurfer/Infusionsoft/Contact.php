<?php

namespace Wildsurfer\Infusionsoft;

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

        if (!empty($data['FirstName']))
            $data['FirstName'] = ucfirst($data['FirstName']);

        if (!empty($data['LastName']))
            $data['LastName'] = ucfirst($data['LastName']);

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
