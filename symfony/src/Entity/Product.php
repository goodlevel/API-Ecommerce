<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'products')] 
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['product:read'])]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 100, nullable: true, unique: true)]
    #[Groups(['product:read'])]
    private $code;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['product:read'])]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['product:read'])]
    private $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private $image;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['product:read'])]
    private $category;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['product:read'])]
    private $price;

    #[ORM\Column(type: 'integer')]
    #[Groups(['product:read'])]
    private $quantity = 0;

    #[ORM\Column(name:'internalReference', type: 'string', length: 100, nullable: true)]
    #[Groups(['product:read'])]
    private $internalReference;

    #[ORM\Column(name:'shellId', type: 'integer')]
    #[Groups(['product:read'])]
    private $shellId = 0;

    #[ORM\Column(name:'inventoryStatus', type: 'string', length: 20)]
    #[Groups(['product:read'])]
    private $inventoryStatus = 'INSTOCK';

    #[ORM\Column(type: 'float')]
    #[Groups(['product:read'])]
    private $rating = 0;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['product:read'])]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['product:read'])]
    private $updatedAt;

    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'product')]
    private $cartItems;

    #[ORM\OneToMany(targetEntity: WishlistItem::class, mappedBy: 'product')]
    private $wishlistItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
        $this->wishlistItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getInternalReference(): ?string
    {
        return $this->internalReference;
    }

    public function setInternalReference(?string $internalReference): self
    {
        $this->internalReference = $internalReference;

        return $this;
    }

    public function getShellId(): ?int
    {
        return $this->shellId;
    }

    public function setShellId(int $shellId): self
    {
        $this->shellId = $shellId;

        return $this;
    }

    public function getInventoryStatus(): ?string
    {
        return $this->inventoryStatus;
    }

    public function setInventoryStatus(string $inventoryStatus): self
    {
        $this->inventoryStatus = $inventoryStatus;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): self
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems[] = $cartItem;
            $cartItem->setProduct($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): self
    {
        if ($this->cartItems->removeElement($cartItem)) {
            if ($cartItem->getProduct() === $this) {
                $cartItem->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WishlistItem>
     */
    public function getWishlistItems(): Collection
    {
        return $this->wishlistItems;
    }

    public function addWishlistItem(WishlistItem $wishlistItem): self
    {
        if (!$this->wishlistItems->contains($wishlistItem)) {
            $this->wishlistItems[] = $wishlistItem;
            $wishlistItem->setProduct($this);
        }

        return $this;
    }

    public function removeWishlistItem(WishlistItem $wishlistItem): self
    {
        if ($this->wishlistItems->removeElement($wishlistItem)) {
            if ($wishlistItem->getProduct() === $this) {
                $wishlistItem->setProduct(null);
            }
        }

        return $this;
    }
}