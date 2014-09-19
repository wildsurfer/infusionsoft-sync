<?php

namespace Wildsurfer\Infusionsoft;

use Exception;

class ContactCollection
{
    protected $stack = array();

    public function __construct(array $contacts = array())
    {
        foreach ($contacts as $contact) {
            $this->create($contact);
        }
        return $this;
    }

    public function diff(ContactCollection $collection)
    {
        $array = $collection->read();
        $data = $this->read();
        $result = array_diff_key($data, $array);
        return new ContactCollection($result);
    }

    public function create($contact = array())
    {
        if (is_array($contact))
            $contact = new Contact($contact);
        elseif (!($contact instanceof Contact))
            throw new Exception('Not instance of `Contact` class');

        $this->stack[$contact->uniqueHash()] = $contact;
        return $this;
    }

    public function read()
    {
        return $this->stack;
    }

    public function count()
    {
        return count($this->stack);
    }
}
