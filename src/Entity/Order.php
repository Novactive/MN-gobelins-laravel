<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="guid", unique=true, nullable=false)
     */
    private string $reference;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=OrderDetails::class, mappedBy="myOrder")
     */
    private $orderDetails;

    /**
     * total
     * @ORM\Column(type="integer")
     */
    private int $amount;

    /**
     * remise
     * @ORM\Column(type="integer")
     */
    private int $discount;

    /**
     * net à payer $amount - $discount
     * @ORM\Column(type="integer")
     */
    private int $netToPay;

    /**
     * reglement (payer) aujourd'hui
     * @ORM\Column(type="integer")
     */
    private int $pay;

    /**
     * rest à payer (plus tard) $netToPay - $pay
     * @ORM\Column(type="integer")
     */
    private int $remainderToPay;

    public function __construct()
    {
        $this->orderDetails = new ArrayCollection();
        $this->reference = Uuid::uuid4()->toString();
        $this->createdAt = new DateTime('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, OrderDetails>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetail(OrderDetails $orderDetail): self
    {
        if (!$this->orderDetails->contains($orderDetail)) {
            $this->orderDetails[] = $orderDetail;
            $orderDetail->setMyOrder($this);
        }

        return $this;
    }

    public function removeOrderDetail(OrderDetails $orderDetail): self
    {
        if ($this->orderDetails->removeElement($orderDetail)) {
            // set the owning side to null (unless already changed)
            if ($orderDetail->getMyOrder() === $this) {
                $orderDetail->setMyOrder(null);
            }
        }

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getNetToPay(): ?int
    {
        return $this->netToPay;
    }

    public function setNetToPay(?int $netToPay): self
    {
        $this->netToPay = $netToPay;

        return $this;
    }

    public function getPay(): ?int
    {
        return $this->pay;
    }

    public function setPay(?int $pay): self
    {
        $this->pay = $pay;

        return $this;
    }

    public function getRemainderToPay(): ?int
    {
        return $this->remainderToPay;
    }

    public function setRemainderToPay(?int $remainderToPay): self
    {
        $this->remainderToPay = $remainderToPay;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }
}
