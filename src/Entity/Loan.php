<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Index('loan_cost_date_idx', ['cost', 'date'])]
class Loan
{
    private const AMOUNT_MIN = 1000;
    private const AMOUNT_MAX = 12000;
    private const AMOUNT_STEP = 500;

    private const INSTALMENTS_MIN = 3;
    private const INSTALMENTS_MAX = 18;
    private const INSTALMENTS_STEP = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(self::AMOUNT_MIN)]
    #[Assert\LessThanOrEqual(self::AMOUNT_MAX)]
    #[Assert\DivisibleBy(self::AMOUNT_STEP)]
    private ?float $amount = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(self::INSTALMENTS_MIN)]
    #[Assert\LessThanOrEqual(self::INSTALMENTS_MAX)]
    #[Assert\DivisibleBy(self::INSTALMENTS_STEP)]
    private ?int $instalments = null;

    #[ORM\Column]
    private ?float $apr = null;

    #[ORM\Column]
    private array $calculations = [];

    #[ORM\Column]
    private ?float $cost = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getInstalments(): ?int
    {
        return $this->instalments;
    }

    public function setInstalments(int $instalments): static
    {
        $this->instalments = $instalments;

        return $this;
    }

    public function getApr(): ?float
    {
        return $this->apr;
    }

    public function setApr(float $apr): static
    {
        $this->apr = $apr;

        return $this;
    }

    public function getCalculations(): array
    {
        return $this->calculations;
    }

    public function setCalculations(array $calculations): static
    {
        $this->calculations = $calculations;

        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
