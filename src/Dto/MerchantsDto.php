<?php

namespace App\Dto;

use App\Entity\Merchant;
use Symfony\Component\Validator\Constraints as Assert;

final class MerchantsDto
{
    /**
     * @var Merchant[]
     */
    #[Assert\Valid]
    public array $merchants = [];
}
