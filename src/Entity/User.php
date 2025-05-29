<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 *
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(
 *     fields={"email"},
 *     message="L'adresse mail est indisponible"
 * )
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  /**
   * @ORM\Id
   *
   * @ORM\GeneratedValue
   *
   * @ORM\Column(type="integer")
   *
   * @Groups("user:follow")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("discussion:read")
   */
  private $id;

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
   */
  private $createdAt;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Assert\Email(message="L'adresse mail est invalide !")
   *
   * @Groups("user:read")
   * @Groups("order:read")
   */
  private $email;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("discussion:read")
   * @Groups("user:follow")
   */
  private $firstname;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("discussion:read")
   * @Groups("user:follow")
   */
  private $lastname;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("discussion:read")
   * @Groups("user:follow")
   */
  private $picture;

  /**
   * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="following", orphanRemoval=true)
   *
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   * @Groups("user:follow")
   */
  private $followers;

  /**
   * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="follower", orphanRemoval=true)
   *
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   */
  private $following;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   */
  private $type = 'user';

  /**
   * @ORM\OneToMany(targetEntity=Order::class, mappedBy="buyer")
   */
  private $purchases;

  /**
   * @ORM\OneToOne(targetEntity=Vendor::class, inversedBy="user", cascade={"persist", "remove"})
   *
   * @Groups("user:read")
   * @Groups("live:read")
   * @Groups("clip:read")
   * @Groups("user:follow")
   * @Groups("discussion:read")
   */
  private $vendor;

  /**
   * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="user", orphanRemoval=true)
   */
  private $comments;

  /**
   * @ORM\OneToMany(targetEntity=ShippingAddress::class, mappedBy="user", orphanRemoval=true)
   *
   * @Groups("user:read")
   */
  private $shippingAddresses;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $phone;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $day;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $month;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $year;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $facebookId;

  /**
   * @ORM\OneToMany(targetEntity=Discussion::class, mappedBy="user")
   *
   * @Groups("user:read")
   */
  private $discussions;

  /**
   * @ORM\OneToMany(targetEntity=Discussion::class, mappedBy="vendor")
   */
  private $vendorDiscussions;

  /**
   * @ORM\OneToMany(targetEntity=SecurityUser::class, mappedBy="user", orphanRemoval=true)
   *
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $securityUsers;

  /**
   * @ORM\OneToMany(targetEntity=Favoris::class, mappedBy="user", orphanRemoval=true)
   *
   * @Groups("user:read")
   */
  private $favoris;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $stripeCustomer;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $appleId;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $googleId;

  public function __construct()
  {
    $this->followers         = new ArrayCollection();
    $this->following         = new ArrayCollection();
    $this->purchases         = new ArrayCollection();
    $this->comments          = new ArrayCollection();
    $this->createdAt         = new DateTime('now', \timezone_open('UTC'));
    $this->shippingAddresses = new ArrayCollection();
    $this->discussions       = new ArrayCollection();
    $this->vendorDiscussions = new ArrayCollection();
    $this->securityUsers     = new ArrayCollection();
    $this->favoris           = new ArrayCollection();
  }

  public function getFullName(): string
  {
    return "{$this->firstname} {$this->lastname}";
  }

  public function getId(): ?int
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

  public function getRoles(): array
  {
    return ['ROLE_USER'];
  }

  public function getPassword(): ?string
  {
    return $this->hash;
  }

  public function getUserIdentifier(): string
  {
    return $this->email;  // ou une autre propriété qui représente l'identifiant unique de l'utilisateur
  }

  public function eraseCredentials(): void
  {
  }

  public function getPushToken(): ?string
  {
    return $this->pushToken;
  }

  public function setPushToken(?string $pushToken): self
  {
    $this->pushToken = $pushToken;

    return $this;
  }

  public function getCreatedAt(): ?DateTimeInterface
  {
    return $this->createdAt;
  }

  public function setCreatedAt(DateTimeInterface $createdAt): self
  {
    $this->createdAt = $createdAt;

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
  {if ($this->followers->removeElement($follower) && $follower->getFollowing() === $this) {
      $follower->setFollowing(null);
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
      $following->setFollower($this);
    }

    return $this;
  }

  public function removeFollowing(Follow $following): self
  {if ($this->following->removeElement($following) && $following->getFollower() === $this) {
      $following->setFollower(null);
    }

    return $this;
  }

  public function getType(): ?string
  {
    return $this->type;
  }

  public function setType(string $type): self
  {
    $this->type = $type;

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
  {if ($this->purchases->removeElement($purchase) && $purchase->getBuyer() === $this) {
      $purchase->setBuyer(null);
    }

    return $this;
  }

  public function getVendor(): ?Vendor
  {
    return $this->vendor;
  }

  public function setVendor(?Vendor $vendor): self
  {
    $this->vendor = $vendor;

    return $this;
  }

  /**
   * @return Collection|Comment[]
   */
  public function getComments(): Collection
  {
    return $this->comments;
  }

  public function addComment(Comment $comment): self
  {
    if (!$this->comments->contains($comment)) {
      $this->comments[] = $comment;
      $comment->setUser($this);
    }

    return $this;
  }

  public function removeComment(Comment $comment): self
  {if ($this->comments->removeElement($comment) && $comment->getUser() === $this) {
      $comment->setUser(null);
    }

    return $this;
  }

  /**
   * @return Collection|ShippingAddress[]
   */
  public function getShippingAddresses(): Collection
  {
    return $this->shippingAddresses;
  }

  public function addShippingAddress(ShippingAddress $shippingAddress): self
  {
    if (!$this->shippingAddresses->contains($shippingAddress)) {
      $this->shippingAddresses[] = $shippingAddress;
      $shippingAddress->setUser($this);
    }

    return $this;
  }

  public function removeShippingAddress(ShippingAddress $shippingAddress): self
  {if ($this->shippingAddresses->removeElement($shippingAddress) && $shippingAddress->getUser() === $this) {
      $shippingAddress->setUser(null);
    }

    return $this;
  }

  public function getPhone(): ?string
  {
    return $this->phone;
  }

  public function setPhone(?string $phone): self
  {
    $this->phone = $phone;

    return $this;
  }

  public function getDay(): ?string
  {
    return $this->day;
  }

  public function setDay(?string $day): self
  {
    $this->day = $day;

    return $this;
  }

  public function getMonth(): ?string
  {
    return $this->month;
  }

  public function setMonth(?string $month): self
  {
    $this->month = $month;

    return $this;
  }

  public function getYear(): ?string
  {
    return $this->year;
  }

  public function setYear(?string $year): self
  {
    $this->year = $year;

    return $this;
  }

  public function getFacebookId(): ?string
  {
    return $this->facebookId;
  }

  public function setFacebookId(?string $facebookId): self
  {
    $this->facebookId = $facebookId;

    return $this;
  }

  /**
   * @return Collection|Discussion[]
   */
  public function getDiscussions(): Collection
  {
    return $this->discussions;
  }

  public function addDiscussion(Discussion $discussion): self
  {
    if (!$this->discussions->contains($discussion)) {
      $this->discussions[] = $discussion;
      $discussion->setUser($this);
    }

    return $this;
  }

  public function removeDiscussion(Discussion $discussion): self
  {if ($this->discussions->removeElement($discussion) && $discussion->getUser() === $this) {
      $discussion->setUser(null);
    }

    return $this;
  }

  /**
   * @return Collection|SecurityUser[]
   */
  public function getSecurityUsers(): Collection
  {
    return $this->securityUsers;
  }

  public function addSecurityUser(SecurityUser $securityUser): self
  {
    if (!$this->securityUsers->contains($securityUser)) {
      $this->securityUsers[] = $securityUser;
      $securityUser->setUser($this);
    }

    return $this;
  }

  public function removeSecurityUser(SecurityUser $securityUser): self
  {if ($this->securityUsers->removeElement($securityUser) && $securityUser->getUser() === $this) {
      $securityUser->setUser(null);
    }

    return $this;
  }

  /**
   * @return Collection|Favoris[]
   */
  public function getFavoris(): Collection
  {
    return $this->favoris;
  }

  public function addFavori(Favoris $favori): self
  {
    if (!$this->favoris->contains($favori)) {
      $this->favoris[] = $favori;
      $favori->setUser($this);
    }

    return $this;
  }

  public function removeFavori(Favoris $favori): self
  {if ($this->favoris->removeElement($favori) && $favori->getUser() === $this) {
      $favori->setUser(null);
    }

    return $this;
  }

  public function getStripeCustomer(): ?string
  {
    return $this->stripeCustomer;
  }

  public function setStripeCustomer(?string $stripeCustomer): self
  {
    $this->stripeCustomer = $stripeCustomer;

    return $this;
  }

  public function getAppleId(): ?string
  {
    return $this->appleId;
  }

  public function setAppleId(?string $appleId): self
  {
    $this->appleId = $appleId;

    return $this;
  }

  public function getGoogleId(): ?string
  {
    return $this->googleId;
  }

  public function setGoogleId(?string $googleId): self
  {
    $this->googleId = $googleId;

    return $this;
  }

  /**
   * @return Collection|Discussion[]
   */
  public function getVendorDiscussions(): Collection
  {
    return $this->vendorDiscussions;
  }

  public function addVendorDiscussion(Discussion $vendorDiscussion): self
  {
    if (!$this->vendorDiscussions->contains($vendorDiscussion)) {
      $this->vendorDiscussions[] = $vendorDiscussion;
      $vendorDiscussion->setVendor($this);
    }

    return $this;
  }

  public function removeVendorDiscussion(Discussion $vendorDiscussion): self
  {if ($this->vendorDiscussions->removeElement($vendorDiscussion) && $vendorDiscussion->getVendor() === $this) {
      $vendorDiscussion->setVendor(null);
    }

    return $this;
  }
}
