<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MoviesRepository")
 */
class Movies
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $movie_id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Lists", inversedBy="movies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $list;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovieId(): ?int
    {
        return $this->movie_id;
    }

    public function setMovieId(int $movie_id): self
    {
        $this->movie_id = $movie_id;

        return $this;
    }

    public function getList(): ?Lists
    {
        return $this->list;
    }

    public function setList(?Lists $list): self
    {
        $this->list = $list;

        return $this;
    }
}
