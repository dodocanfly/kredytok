<?php

namespace App\Service;

use App\Entity\Loan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoanService
{
    private const APR = 0.08; // Annual Percentage Rate
    private const INSTALMENTS_PER_YEAR = 12;

    private float $instalmentAmount;
    private \DateTimeImmutable $calculationDate;
    private array $errors = [];

    public function __construct(
        private readonly Loan                   $loan,
        private readonly ValidatorInterface     $validator,
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->calculationDate = new \DateTimeImmutable();
    }

    public function createLoan(): array
    {
        try {
            $this->validate($this->loan);

            $this->loan->setApr(self::APR);
            $this->loan->setCalculations($this->getLoanSchedule());
            $this->loan->setDate($this->calculationDate);
            $this->loan->setCost($this->getTotalLoanCost());

        } catch (\Throwable $exception) {
            $this->addError($exception->getMessage());
        }

        if ($this->hasErrors()) {
            return $this->getErrors();
        }

        $this->saveLoan();

        return $this->getLoanData($this->loan);
    }

    public function deactivate(): array
    {
        if (empty($this->loan->getId())) {
            return $this->addError('Loan calculation not found')->getErrors();
        }

        $this->loan->setActive(false);

        $this->saveLoan();

        return [
            'status' => 'SUCCESS',
        ];
    }

    private function validate(Loan $loan): void
    {
        $errors = $this->validator->validate($loan);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addError($error->getMessage(), $error->getPropertyPath(), $error->getInvalidValue());
            }
        }
    }

    private function saveLoan(): void
    {
        $this->entityManager->persist($this->loan);
        $this->entityManager->flush();
    }

    private function getLoanSchedule(): array
    {
        $remainingCapital = $this->loan->getAmount();
        $schedule = [];
        for ($instalmentNumber = 1; $instalmentNumber <= $this->loan->getInstalments(); $instalmentNumber++) {
            $interest = $this->calculateInterest($remainingCapital, $instalmentNumber);
            if ($instalmentNumber === $this->loan->getInstalments()) {
                $interestSum = array_sum(array_column($schedule, 'interest'));
                $interest = round($this->getTotalLoanCost() - $interestSum, 2);
            }
            $capital = round($this->getInstalmentAmount() - $interest, 2);

            $schedule[$instalmentNumber] = [
                'instalmentNumber' => $instalmentNumber,
                'month' => $this->getInstalmentMonth($instalmentNumber),
                'instalmentAmount' => $this->getInstalmentAmount(),
                'interest' => $interest,
                'capital' => $capital,
                'remainingCapital' => $remainingCapital,
            ];
            $remainingCapital = round($remainingCapital - $capital, 2);
        }

        return array_values($schedule);
    }

    private function calculateInterest(float $remainingCapital, int $instalmentNumber): float
    {
        $instalmentMonthDays = $this->getInstalmentMonthDays($instalmentNumber);
        $instalmentYearDays = $this->getInstalmentYearDays($instalmentNumber);

        return round($remainingCapital * self::APR * $instalmentMonthDays / $instalmentYearDays, 2);
    }

    private function getInstalmentAmount(): float
    {
        if (!isset($this->instalmentAmount)) {
            $this->instalmentAmount = $this->calculateInstalmentAmount();
        }

        return $this->instalmentAmount;
    }

    private function calculateInstalmentAmount(): float
    {
        $aprByInstalmentsPerYear = self::APR / self::INSTALMENTS_PER_YEAR;
        $aprByInstalmentsPerYearPow = pow(1 + $aprByInstalmentsPerYear, $this->loan->getInstalments());

        $instalment = $this->loan->getAmount() * (
                ($aprByInstalmentsPerYear * $aprByInstalmentsPerYearPow) /
                ($aprByInstalmentsPerYearPow - 1)
            );

        return round($instalment, 2);
    }

    private function getInstalmentMonth(int $instalmentNumber): string
    {
        return $this->getInstalmentDate($instalmentNumber)->format('Y-m');
    }

    private function getInstalmentDate(int $instalmentNumber): \DateTimeImmutable
    {
        return $this->calculationDate->modify('first day of +' . $instalmentNumber . ' months');
    }

    private function getInstalmentMonthDays(int $instalmentNumber): int
    {
        return (int)$this->getInstalmentDate($instalmentNumber)->format('t');
    }

    private function getInstalmentYearDays(int $instalmentNumber): int
    {
        return $this->getInstalmentDate($instalmentNumber)->format('L') === '1' ? 366 : 365;
    }

    private function getTotalLoanCost(): float
    {
        return $this->getTotalLoanValue() - $this->loan->getAmount();
    }

    private function getTotalLoanValue(): float
    {
        return $this->loan->getInstalments() * $this->getInstalmentAmount();
    }

    private function addError(string $message, string $field = '', mixed $invalidValue = ''): self
    {
        if (!empty($field)) {
            $message = '`' . $field . '`: ' . $message;
        }

        if (!empty($invalidValue)) {
            $message .= ' (invalid value: ' . $invalidValue . ')';
        }

        $this->errors[] = [
            'message' => $message,
        ];

        return $this;
    }

    private function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    private function getErrors(): array
    {
        return [
            'errors' => $this->errors,
        ];
    }

    private function getLoanData(Loan $loan): array
    {
        return [
            'data' => [
                'metric' => [
                    'calculationDate' => $loan->getDate()->format('Y-m-d'),
                    'instalments' => $loan->getInstalments(),
                    'amount' => $loan->getAmount(),
                    'interestRate' => self::APR,
                ],
                'schedule' => $loan->getCalculations(),
            ]
        ];
    }
}
