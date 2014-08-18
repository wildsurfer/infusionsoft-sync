<?php

namespace Wildsurfer\Infusionsoft;

class Sync
{
    protected $options;
    protected $isdk;

    public function __construct(array $options)
    {
    }

    public function pull()
    {
        $allContacts = array();

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
                $query,
                $fields
            );

            if (is_string($results))
                throw new SyncException($results);
            else if (is_array($results)) {
                foreach ($results as $r) {
                    $c = new Client($r);
                    $allContacts[$r->uniqueHash()] = $c;
                }
            }

            if(count($results) < $limit) break;
            $page++;
        }
        return $allContacts;
    }

    public function push(ContactCollection $c)
    {
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
