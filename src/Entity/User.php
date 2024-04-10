<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "integer", unique: true)]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column]
    private $username;

    #[ORM\Column]
    private $roles;

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_API_KEY';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): void
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function addRole(string $role): void
    {
        $role = strtoupper($role);

        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function eraseCredentials(): void {}

     /**
     * @return mixed[]
     */
    public function __serialize(): array
    {
        return [
            $this->username,
            $this->id,
        ];
    }

    /**
     * @param mixed[] $data
     */
    public function __unserialize(array $data): void
    {
        [
            $this->username,
            $this->id,
        ] = $data;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize($data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public static function createFromPayload($username, array $payload)
    {
        $user = new self();
        $user->setUsername($payload['username']);
        if (isset($payload['roles'])) {
            $user->setRoles($payload['roles']);
        }

        return $user;
    }
}
