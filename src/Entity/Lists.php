<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ListsRepository")
 */
class Lists
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Le titre doit comporter au minimum {{ limit }} caractères",
     *      maxMessage = "Le titre doit comporter au maximum {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(
     *      max = 255,
     *      maxMessage = "Le titre doit comporter au maximum {{ limit }} caractères",
     * )
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     */
    private $can_erase = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_shared = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $last_update;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users", inversedBy="lists")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Movies", mappedBy="list", orphanRemoval=true)
     */
    private $movies;
  
    public function __construct()
    {
        $this->last_update = new \DateTime();
        $this->movies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCanErase(): ?bool
    {
        return $this->can_erase;
    }

    public function setCanErase(bool $can_erase): self
    {
        $this->can_erase = $can_erase;

        return $this;
    }

    public function getIsShared(): ?bool
    {
        return $this->is_shared;
    }

    public function setIsShared(bool $is_shared): self
    {
        $this->is_shared = $is_shared;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->last_update;
    }

    public function setLastUpdate(\DateTimeInterface $last_update): self
    {
        $this->last_update = $last_update;

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|Movies[]
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movies $movie): self
    {
        if (!$this->movies->contains($movie)) {
            $this->movies[] = $movie;
            $movie->setList($this);
        }

        return $this;
    }

    public function removeMovie(Movies $movie): self
    {
        if ($this->movies->contains($movie)) {
            $this->movies->removeElement($movie);
            // set the owning side to null (unless already changed)
            if ($movie->getList() === $this) {
                $movie->setList(null);
            }
        }

        return $this;
    }
}
