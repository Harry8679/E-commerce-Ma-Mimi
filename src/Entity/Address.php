<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Address
{
    // Constantes pour les types d'adresse
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_BILLING = 'billing';
    public const TYPE_BOTH = 'both';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom/prénom est obligatoire')]
    private ?string $fullName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9+\s\-()]+$/',
        message: 'Le numéro de téléphone n\'est pas valide'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $streetComplement = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[0-9]{5}$/',
        message: 'Le code postal doit contenir 5 chiffres'
    )]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    private ?string $country = 'France';

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: [self::TYPE_SHIPPING, self::TYPE_BILLING, self::TYPE_BOTH],
        message: 'Le type d\'adresse n\'est pas valide'
    )]
    private ?string $type = self::TYPE_BOTH;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s, %s %s',
            $this->fullName,
            $this->street,
            $this->postalCode,
            $this->city
        );
    }

    public function getFullAddress(): string
    {
        $address = $this->street;
        
        if ($this->streetComplement) {
            $address .= "\n" . $this->streetComplement;
        }
        
        $address .= "\n" . $this->postalCode . ' ' . $this->city;
        $address .= "\n" . $this->country;
        
        return $address;
    }

    /**
     * Obtenir le label du type d'adresse
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SHIPPING => 'Livraison',
            self::TYPE_BILLING => 'Facturation',
            self::TYPE_BOTH => 'Livraison et Facturation',
            default => 'Non défini',
        };
    }

    /**
     * Vérifier si l'adresse peut être utilisée pour la livraison
     */
    public function canBeUsedForShipping(): bool
    {
        return in_array($this->type, [self::TYPE_SHIPPING, self::TYPE_BOTH]);
    }

    /**
     * Vérifier si l'adresse peut être utilisée pour la facturation
     */
    public function canBeUsedForBilling(): bool
    {
        return in_array($this->type, [self::TYPE_BILLING, self::TYPE_BOTH]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;
        return $this;
    }

    public function getStreetComplement(): ?string
    {
        return $this->streetComplement;
    }

    public function setStreetComplement(?string $streetComplement): static
    {
        $this->streetComplement = $streetComplement;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}