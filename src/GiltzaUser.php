<?php

namespace Giltza\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class GiltzaUser implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;
    protected array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getId(): mixed
    {
        return $this->response['dni'];
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
