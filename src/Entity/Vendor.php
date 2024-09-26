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
* @UniqueEntity(
*  fields={"pseudo"},
*  message="Le pseudo est indisponible"
* )
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
   * @Groups("category:read")
   * @Groups("order:read")
   * @Groups("product:read")
   * @Groups("user:follow")
   */
  private $id;

  /**
   * @ORM\OneToMany(targetEntity=Live::class, mappedBy="vendor")
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
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("user:follow")
   */
  private $company;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("user:follow")
   */
  private $summary;

  /**
   * @ORM\OneToMany(targetEntity=Product::class, mappedBy="vendor")
   * @ORM\OrderBy({"title" = "ASC"})
   * @Groups("user:read")
   */
  private $products;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("user:follow")
   */
  private $businessType;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("product:read")
   * @Groups("order:read")
   * @Groups("discussion:read")
   * @Groups("user:follow")
   */
  private $pseudo;

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
   */
  private $sales;

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
   * @ORM\OrderBy({"createdAt" = "DESC"})
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
   * @Groups("user:read")
   */
  private $user;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   */
  private $country;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   */
  private $countryCode;

  /**
   * @ORM\OneToMany(targetEntity=Promotion::class, mappedBy="vendor", orphanRemoval=true)
   * @ORM\OrderBy({"createdAt" = "DESC"})
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   */
  private $promotions;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $stripeAcc;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $personId;

  
  public function __construct()
  {
    $this->lives = new ArrayCollection();
    $this->clips = new ArrayCollection();
    $this->products = new ArrayCollection();
    $this->sales = new ArrayCollection();
    $this->withdraws = new ArrayCollection();
    $this->bankAccounts = new ArrayCollection();
    $this->promotions = new ArrayCollection();
    $this->pending = "0.00";
    $this->available = "0.00";
    $this->verified = false;
    $this->countryCode = "FR";
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

  public function getPseudo(): ?string
  {
    return $this->pseudo;
  }

  public function setPseudo(?string $pseudo): self
  {
    $this->pseudo = $pseudo;

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

  public function getCountry(): ?string
  {
    return $this->country;
  }

  public function setCountry(?string $country): self
  {
    $this->country = $country;

    return $this;
  }

  public function getCountryCode(): ?string
  {
    return $this->countryCode;
  }

  public function setCountryCode(?string $countryCode): self
  {
    $this->countryCode = $countryCode;

    return $this;
  }

  /**
   * @return Collection|Promotion[]
   */
  public function getPromotions(): Collection
  {
    return $this->promotions;
  }

  public function addPromotion(Promotion $promotion): self
  {
    if (!$this->promotions->contains($promotion)) {
      $this->promotions[] = $promotion;
      $promotion->setVendor($this);
    }

    return $this;
  }

  public function removePromotion(Promotion $promotion): self
  {
    if ($this->promotions->removeElement($promotion)) {
          // set the owning side to null (unless already changed)
      if ($promotion->getVendor() === $this) {
        $promotion->setVendor(null);
      }
    }

    return $this;
  }

  public function getStripeAcc(): ?string
  {
    return $this->stripeAcc;
  }

  public function setStripeAcc(?string $stripeAcc): self
  {
    $this->stripeAcc = $stripeAcc;

    return $this;
  }

  public function getPersonId(): ?string
  {
    return $this->personId;
  }

  public function setPersonId(?string $personId): self
  {
    $this->personId = $personId;

    return $this;
  }
}
