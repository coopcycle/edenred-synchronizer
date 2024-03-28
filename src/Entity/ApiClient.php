<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class ApiClient
{
    #[ORM\Id]
    #[ORM\Column(type: "integer", unique: true)]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column]
    private $name;

    #[ORM\Column]
    private $apiKey;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}
