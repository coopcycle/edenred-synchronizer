<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class File
{
    #[ORM\Id]
    #[ORM\Column(type: "integer", unique: true)]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column]
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

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
}
