<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\ParseAndSendMerchants;
use App\Dto\MerchantsDto;
use Doctrine\DBAL\SQL\Parser;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/merchants/{siret}'
    ),
    new Post(
        uriTemplate: '/merchants',
        input: MerchantsDto::class,
        controller: ParseAndSendMerchants::class
    )
])]
#[ORM\Entity()]
class Merchant
{
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    #[ORM\GeneratedValue("NONE")]
    #[ApiProperty(identifier: true)]
    private $siret;

    #[ORM\Column(nullable: true)]
    private $addInfo;

    #[ORM\Column(nullable: true)]
    private $merchantId;

    #[ORM\Column(type: "boolean", nullable: true)]
    private $acceptsTRCard;

    #[ORM\Column(length: 100, nullable: true)]
    private $address;

    #[ORM\Column(length: 100, nullable: true)]
    private $city;

    #[ORM\Column(length: 5, nullable: true)]
    private $postalCode;

    public function __construct(string $siret)
    {
        $this->siret = $siret;
    }

    public function getSiret()
    {
        return $this->siret;
    }

    public function setSiret($siret)
    {
        $this->siret = $siret;

        return $this;
    }

    public function getAddInfo()
    {
        return $this->addInfo;
    }

    public function setAddInfo($addInfo)
    {
        $this->addInfo = $addInfo;

        return $this;
    }

    public function getMerchantId()
    {
        return $this->merchantId;
    }

    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    public function getAcceptsTRCard()
    {
        return $this->acceptsTRCard;
    }

    public function setAcceptsTRCard($acceptsTRCard)
    {
        $this->acceptsTRCard = $acceptsTRCard;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }
}
