<?php

namespace App\Entity;

use App\Entity\Role;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VendorRepository")
 */
class Vendor
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=Live::class, mappedBy="vendor")
     * @Groups("user:read")
     */
    private $lives;

    /**
     * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="vendor")
     * @Groups("user:read")
     */
    private $clips;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("order:read")
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     * @Groups("clip:edit")
     */
    private $summary;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="vendor")
     * @ORM\OrderBy({"title" = "ASC"})
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $products;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     * @Groups("product:read")
     */
    private $businessType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("product:read")
     * @Groups("order:read")
     */
    private $businessName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("user:read")
     */
    private $dob;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     */
    private $siren;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     */
    private $zip;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="vendor")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @Groups("user:read")
     */
    private $sales;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("user:read")
     */
    private $total;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("user:read")
     */
    private $pending;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("user:read")
     */
    private $available;

    /**
     * @ORM\OneToMany(targetEntity=Withdraw::class, mappedBy="vendor")
     * @Groups("user:read")
     */
    private $withdraws;

    /**
     * @ORM\OneToMany(targetEntity=BankAccount::class, mappedBy="vendor")
     * @Groups("user:read")
     */
    private $bankAccounts;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups("user:read")
     */
    private $verified;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="vendor", cascade={"persist", "remove"})
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("order:read")
     */
    private $user;

    
    public function __construct()
    {
        $this->lives = new ArrayCollection();
        $this->clips = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->withdraws = new ArrayCollection();
        $this->bankAccounts = new ArrayCollection();
        $this->total = "0.00";
        $this->pending = "0.00";
        $this->available = "0.00";
        $this->verified = false;
    }


    public function getId()
    {
        return $this->id;
    }
  
    /**
     * @return Collection|Live[]
     */
    public function getLives(): Collection
    {
        return $this->lives;
    }

    public function addLive(Live $live): self
    {
        if (!$this->lives->contains($live)) {
            $this->lives[] = $live;
            $live->setVendor($this);
        }

        return $this;
    }

    public function removeLive(Live $live): self
    {
        if ($this->lives->removeElement($live)) {
            // set the owning side to null (unless already changed)
            if ($live->getVendor() === $this) {
                $live->setVendor(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|Clip[]
     */
    public function getClips(): Collection
    {
        return $this->clips;
    }

    public function addClip(Clip $clip): self
    {
        if (!$this->clips->contains($clip)) {
            $this->clips[] = $clip;
            $clip->setVendor($this);
        }

        return $this;
    }

    public function removeClip(Clip $clip): self
    {
        if ($this->clips->removeElement($clip)) {
            // set the owning side to null (unless already changed)
            if ($clip->getVendor() === $this) {
                $clip->setVendor(null);
            }
        }

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

  
    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setVendor($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getVendor() === $this) {
                $product->setVendor(null);
            }
        }

        return $this;
    }


    public function getBusinessType(): ?string
    {
        return $this->businessType;
    }

    public function setBusinessType(?string $businessType): self
    {
        $this->businessType = $businessType;

        return $this;
    }

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): self
    {
        $this->dob = $dob;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): self
    {
        $this->siren = $siren;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }
  
    /**
     * @return Collection|Order[]
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Order $sale): self
    {
        if (!$this->sales->contains($sale)) {
            $this->sales[] = $sale;
            $sale->setVendor($this);
        }

        return $this;
    }

    public function removeSale(Order $sale): self
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getVendor() === $this) {
                $sale->setVendor(null);
            }
        }

        return $this;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(?string $businessName): self
    {
        $this->businessName = $businessName;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(?string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getPending(): ?string
    {
        return $this->pending;
    }

    public function setPending(?string $pending): self
    {
        $this->pending = $pending;

        return $this;
    }

    public function getAvailable(): ?string
    {
        return $this->available;
    }

    public function setAvailable(?string $available): self
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return Collection|Withdraw[]
     */
    public function getWithdraws(): Collection
    {
        return $this->withdraws;
    }

    public function addWithdraw(Withdraw $withdraw): self
    {
        if (!$this->withdraws->contains($withdraw)) {
            $this->withdraws[] = $withdraw;
            $withdraw->setVendor($this);
        }

        return $this;
    }

    public function removeWithdraw(Withdraw $withdraw): self
    {
        if ($this->withdraws->removeElement($withdraw)) {
            // set the owning side to null (unless already changed)
            if ($withdraw->getVendor() === $this) {
                $withdraw->setVendor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BankAccount[]
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
    }

    public function addBankAccount(BankAccount $bankAccount): self
    {
        if (!$this->bankAccounts->contains($bankAccount)) {
            $this->bankAccounts[] = $bankAccount;
            $bankAccount->setVendor($this);
        }

        return $this;
    }

    public function removeBankAccount(BankAccount $bankAccount): self
    {
        if ($this->bankAccounts->removeElement($bankAccount)) {
            // set the owning side to null (unless already changed)
            if ($bankAccount->getVendor() === $this) {
                $bankAccount->setVendor(null);
            }
        }

        return $this;
    }

    public function getVerified(): ?bool
    {
        return $this->verified;
    }

    public function setVerified(?bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setVendor(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getVendor() !== $this) {
            $user->setVendor($this);
        }

        $this->user = $user;

        return $this;
    }
}
