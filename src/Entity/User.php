<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'user.email.already_used')]
class User extends TimestampableEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_PARTICIPANT = 'ROLE_PARTICIPANT';
    public const ROLE_ORGANIZER = 'ROLE_ORGANIZER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public const SUPPORTED_LOCALES = ['en', 'fr'];

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read'])]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read', 'user:public'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read', 'user:public'])]
    private ?string $lastName = null;

    /** Preferred language, used for emails sent outside of an HTTP request. */
    #[ORM\Column(length: 2, options: ['default' => 'en'])]
    #[Groups(['user:read'])]
    private string $locale = 'en';

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower($email);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Every user is at least a participant; the role hierarchy (security.yaml) does the rest.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, self::ROLE_PARTICIPANT]));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function addRole(string $role): static
    {
        return $this->setRoles([...$this->roles, $role]);
    }

    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->getRoles(), true);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Groups(['user:read', 'user:public'])]
    #[SerializedName('fullName')]
    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    /** Uses the global str_initials() helper from src/helpers.php. */
    #[Groups(['user:read', 'user:public'])]
    public function getInitials(): string
    {
        return str_initials($this->getFullName());
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = \in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
