<?php

namespace Wildsurfer\Infusionsoft;

class Contact
{
    protected $data = array();
    protected $id = null;
    protected $errorMessage = '';

    protected $isCreated = false;
    protected $isUpdated = false;
    protected $isFailed = false;
    protected $isSkipped = false;

    public function __construct(array $data = array())
    {
        if (!empty($data['Id'])) {
            $this->setId($data['Id']);
            unset($data['Id']);
        }
        ksort($data);
        $this->data = $data;
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
        return md5(var_export($this->data, true));
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
