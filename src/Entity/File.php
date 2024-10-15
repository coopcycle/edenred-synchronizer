<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity()]
class File
{
    #[ORM\Id]
    #[ORM\Column(type: "integer", unique: true)]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column]
    private $name;

    #[ORM\Column(type: "boolean")]
    private $sent = false;

    #[ORM\Column(nullable: true)]
    private $errors;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    public ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    public ?\DateTimeImmutable $updatedAt = null;

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

    public function getSent()
    {
        return $this->sent;
    }

    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }
}
