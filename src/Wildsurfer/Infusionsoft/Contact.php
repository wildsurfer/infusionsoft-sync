<?php

namespace Wildsurfer\Infusionsoft;

class Contact
{
    protected $data;

    public function __construct(array $data = array())
    {
    }


    public function field(string $name)
    {
        $fields = $this->getConfigFields();
        if (empty($fields[$name]))
            throw new SyncException('Field not found in config!');

        if (empty($data[$name]))
            return null;
        else
            return $data[$name];
    }

    public function uniqueHash()
    {
    }
}
