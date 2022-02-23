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
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Un compte est associé à cette adresse mail"
 * )
 */
class Vendor implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email(message="L'adresse mail est invalide !")
     * @Groups("vendor:read")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pushToken;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("vendor:read")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=Live::class, mappedBy="vendor")
     * @Groups("vendor:read")
     */
    private $lives;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="vendor")
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="vendor")
     * @Groups("vendor:read")
     */
    private $clips;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("product:read")
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("product:read")
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     * @Groups("clip:edit")
     */
    private $summary;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $picture;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="vendor")
     * @ORM\OrderBy({"title" = "ASC"})
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $products;

    /**
     * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="following")
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $followers;

    /**
     * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="vendor")
     * @Groups("vendor:read")
     */
    private $following;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     * @Groups("product:read")
     */
    private $businessType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("product:read")
     */
    private $businessName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("vendor:read")
     */
    private $dob;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     */
    private $siren;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeAcc;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeCus;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="vendor")
     * @Groups("vendor:read")
     */
    private $sales;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="buyer")
     * @Groups("vendor:read")
     */
    private $purchases;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("vendor:read")
     */
    private $total;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("vendor:read")
     */
    private $pending;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("vendor:read")
     */
    private $available;

    /**
     * @ORM\OneToMany(targetEntity=Withdraw::class, mappedBy="vendor")
     * @Groups("vendor:read")
     */
    private $withdraws;

    /**
     * @ORM\OneToMany(targetEntity=BankAccount::class, mappedBy="vendor")
     * @Groups("vendor:read")
     */
    private $bankAccounts;

    
    public function __construct()
    {
        $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
        $this->lives = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->clips = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->purchases = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->withdraws = new ArrayCollection();
        $this->total = "0.00";
        $this->pending = "0.00";
        $this->available = "0.00";
        $this->bankAccounts = new ArrayCollection();
    }

    public function getClassName() {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getFullname() {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getRoles() {

        return ['ROLE_VENDOR'];
    }

    public function getPassword() {
        return $this->hash;
    }

    public function getSalt() {}
    
    public function getUsername() {
        return $this->email;
    }

    public function eraseCredentials() {}


    public function getPushToken(): ?string
    {
        return $this->pushToken;
    }

    public function setPushToken(?string $pushToken): self
    {
        $this->pushToken = $pushToken;

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

    /**
     * @return Collection|Live[]
     */
    public function getLives(): Collection
    {
        return $this->lives;
    }

    public function addLife(Live $life): self
    {
        if (!$this->lives->contains($life)) {
            $this->lives[] = $life;
            $life->setVendor($this);
        }

        return $this;
    }

    public function removeLife(Live $life): self
    {
        if ($this->lives->removeElement($life)) {
            // set the owning side to null (unless already changed)
            if ($life->getVendor() === $this) {
                $life->setVendor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setVendor($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getVendor() === $this) {
                $message->setVendor(null);
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

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

    /**
     * @return Collection|Follow[]
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(Follow $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers[] = $follower;
            $follower->setFollowing($this);
        }

        return $this;
    }

    public function removeFollower(Follow $follower): self
    {
        if ($this->followers->removeElement($follower)) {
            // set the owning side to null (unless already changed)
            if ($follower->getFollowing() === $this) {
                $follower->setFollowing(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Follow[]
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(Follow $following): self
    {
        if (!$this->following->contains($following)) {
            $this->following[] = $following;
            $following->setVendor($this);
        }

        return $this;
    }

    public function removeFollowing(Follow $following): self
    {
        if ($this->following->removeElement($following)) {
            // set the owning side to null (unless already changed)
            if ($following->getVendor() === $this) {
                $following->setVendor(null);
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

    public function getStripeAcc(): ?string
    {
        return $this->stripeAcc;
    }

    public function setStripeAcc(?string $stripeAcc): self
    {
        $this->stripeAcc = $stripeAcc;

        return $this;
    }

    public function getStripeCus(): ?string
    {
        return $this->stripeCus;
    }

    public function setStripeCus(?string $stripeCus): self
    {
        $this->stripeCus = $stripeCus;

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

    /**
     * @return Collection|Order[]
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(Order $purchase): self
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases[] = $purchase;
            $purchase->setBuyer($this);
        }

        return $this;
    }

    public function removePurchase(Order $purchase): self
    {
        if ($this->purchases->removeElement($purchase)) {
            // set the owning side to null (unless already changed)
            if ($purchase->getBuyer() === $this) {
                $purchase->setBuyer(null);
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
}
