<?php

namespace Wildsurfer\Infusionsoft;

use Exception;

class ContactCollection
{
    protected $stack;

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
