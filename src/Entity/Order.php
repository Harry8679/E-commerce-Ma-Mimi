<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $shippingCost = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $taxAmount = '0';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerNote = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

    // Adresse de livraison (dénormalisée pour l'historique)
    #[ORM\Column(length: 255)]
    private ?string $shippingFullName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingStreet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingStreetComplement = null;

    #[ORM\Column(length: 10)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingCountry = null;

    // Adresse de facturation (dénormalisée pour l'historique)
    #[ORM\Column(length: 255)]
    private ?string $billingFullName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $billingStreet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $billingStreetComplement = null;

    #[ORM\Column(length: 10)]
    private ?string $billingPostalCode = null;

    #[ORM\Column(length: 255)]
    private ?string $billingCity = null;

    #[ORM\Column(length: 255)]
    private ?string $billingCountry = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    #[ORM\OneToOne(mappedBy: 'order', cascade: ['persist', 'remove'])]
    private ?Payment $payment = null;

    #[ORM\OneToOne(mappedBy: 'order', cascade: ['persist', 'remove'])]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->generateOrderNumber();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateOrderNumber(): void
    {
        $this->orderNumber = 'CMD-' . strtoupper(uniqid());
    }

    public function __toString(): string
    {
        return $this->orderNumber ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PAID => 'Payée',
            self::STATUS_PROCESSING => 'En préparation',
            self::STATUS_SHIPPED => 'Expédiée',
            self::STATUS_DELIVERED => 'Livrée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
            default => 'Inconnu',
        };
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getShippingCost(): ?string
    {
        return $this->shippingCost;
    }

    public function setShippingCost(string $shippingCost): static
    {
        $this->shippingCost = $shippingCost;
        return $this;
    }

    public function getTaxAmount(): ?string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): static
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    public function getSubTotal(): float
    {
        return (float)$this->totalAmount - (float)$this->shippingCost - (float)$this->taxAmount;
    }

    public function getCustomerNote(): ?string
    {
        return $this->customerNote;
    }

    public function setCustomerNote(?string $customerNote): static
    {
        $this->customerNote = $customerNote;
        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;
        return $this;
    }

    // Getters et setters pour l'adresse de livraison
    public function getShippingFullName(): ?string
    {
        return $this->shippingFullName;
    }

    public function setShippingFullName(string $shippingFullName): static
    {
        $this->shippingFullName = $shippingFullName;
        return $this;
    }

    public function getShippingPhone(): ?string
    {
        return $this->shippingPhone;
    }

    public function setShippingPhone(?string $shippingPhone): static
    {
        $this->shippingPhone = $shippingPhone;
        return $this;
    }

    public function getShippingStreet(): ?string
    {
        return $this->shippingStreet;
    }

    public function setShippingStreet(string $shippingStreet): static
    {
        $this->shippingStreet = $shippingStreet;
        return $this;
    }

    public function getShippingStreetComplement(): ?string
    {
        return $this->shippingStreetComplement;
    }

    public function setShippingStreetComplement(?string $shippingStreetComplement): static
    {
        $this->shippingStreetComplement = $shippingStreetComplement;
        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(string $shippingPostalCode): static
    {
        $this->shippingPostalCode = $shippingPostalCode;
        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;
        return $this;
    }

    public function getShippingCountry(): ?string
    {
        return $this->shippingCountry;
    }

    public function setShippingCountry(string $shippingCountry): static
    {
        $this->shippingCountry = $shippingCountry;
        return $this;
    }

    public function getShippingFullAddress(): string
    {
        $address = $this->shippingStreet;
        
        if ($this->shippingStreetComplement) {
            $address .= "\n" . $this->shippingStreetComplement;
        }
        
        $address .= "\n" . $this->shippingPostalCode . ' ' . $this->shippingCity;
        $address .= "\n" . $this->shippingCountry;
        
        return $address;
    }

    // Getters et setters pour l'adresse de facturation
    public function getBillingFullName(): ?string
    {
        return $this->billingFullName;
    }

    public function setBillingFullName(string $billingFullName): static
    {
        $this->billingFullName = $billingFullName;
        return $this;
    }

    public function getBillingPhone(): ?string
    {
        return $this->billingPhone;
    }

    public function setBillingPhone(?string $billingPhone): static
    {
        $this->billingPhone = $billingPhone;
        return $this;
    }

    public function getBillingStreet(): ?string
    {
        return $this->billingStreet;
    }

    public function setBillingStreet(string $billingStreet): static
    {
        $this->billingStreet = $billingStreet;
        return $this;
    }

    public function getBillingStreetComplement(): ?string
    {
        return $this->billingStreetComplement;
    }

    public function setBillingStreetComplement(?string $billingStreetComplement): static
    {
        $this->billingStreetComplement = $billingStreetComplement;
        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(string $billingPostalCode): static
    {
        $this->billingPostalCode = $billingPostalCode;
        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(string $billingCity): static
    {
        $this->billingCity = $billingCity;
        return $this;
    }

    public function getBillingCountry(): ?string
    {
        return $this->billingCountry;
    }

    public function setBillingCountry(string $billingCountry): static
    {
        $this->billingCountry = $billingCountry;
        return $this;
    }

    public function getBillingFullAddress(): string
    {
        $address = $this->billingStreet;
        
        if ($this->billingStreetComplement) {
            $address .= "\n" . $this->billingStreetComplement;
        }
        
        $address .= "\n" . $this->billingPostalCode . ' ' . $this->billingCity;
        $address .= "\n" . $this->billingCountry;
        
        return $address;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeImmutable $shippedAt): static
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
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

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }
        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        // unset the owning side of the relation if necessary
        if ($payment === null && $this->payment !== null) {
            $this->payment->setOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($payment !== null && $payment->getOrder() !== $this) {
            $payment->setOrder($this);
        }

        $this->payment = $payment;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        // unset the owning side of the relation if necessary
        if ($invoice === null && $this->invoice !== null) {
            $this->invoice->setOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($invoice !== null && $invoice->getOrder() !== $this) {
            $invoice->setOrder($this);
        }

        $this->invoice = $invoice;
        return $this;
    }

    public function copyAddressFromAddress(Address $address, string $type = 'shipping'): void
    {
        if ($type === 'shipping') {
            $this->setShippingFullName($address->getFullName());
            $this->setShippingPhone($address->getPhone());
            $this->setShippingStreet($address->getStreet());
            $this->setShippingStreetComplement($address->getStreetComplement());
            $this->setShippingPostalCode($address->getPostalCode());
            $this->setShippingCity($address->getCity());
            $this->setShippingCountry($address->getCountry());
        } else {
            $this->setBillingFullName($address->getFullName());
            $this->setBillingPhone($address->getPhone());
            $this->setBillingStreet($address->getStreet());
            $this->setBillingStreetComplement($address->getStreetComplement());
            $this->setBillingPostalCode($address->getPostalCode());
            $this->setBillingCity($address->getCity());
            $this->setBillingCountry($address->getCountry());
        }
    }
}