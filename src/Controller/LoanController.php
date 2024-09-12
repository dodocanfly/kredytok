<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/loan')]
class LoanController extends AbstractController
{
    #[Route('/calculate', name: 'app_calculate', methods: ['POST'])]
    public function calculate(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): JsonResponse
    {
        $loan = new Loan();
        $loan->setUser($this->getUser())
            ->setAmount((int)$request->getPayload()->get('amount'))
            ->setInstalments((int)$request->getPayload()->get('instalments'));

        $loanService = new LoanService($loan, $validator, $entityManager);

        return $this->json($loanService->createLoan());
    }

    #[Route('/deactivate/{loanId}', name: 'app_deactivate', methods: ['PUT'])]
    public function deactivate(ValidatorInterface $validator, EntityManagerInterface $entityManager, int $loanId): JsonResponse
    {
        $loan = $entityManager->getRepository(Loan::class)->findOneBy([
            'id' => $loanId,
            'user' => $this->getUser(),
        ]) ?? new Loan();

        $loanService = new LoanService($loan, $validator, $entityManager);

        return $this->json($loanService->deactivate());
    }

    #[Route('/list', name: 'app_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $loanList = [];
        $criteria = ['user' => $this->getUser()];
        if ((int)$request->get('inactiveOnly') === 1) {
            $criteria['active'] = 0;
        }

        $loans = $entityManager->getRepository(Loan::class)->findBy($criteria, ['cost' => 'DESC'], 4);
        foreach ($loans as $loan) {
            $loanList[] = [
                'id' => $loan->getId(),
                'amount' => $loan->getAmount(),
                'instalments' => $loan->getInstalments(),
                'apr' => $loan->getApr(),
                'cost' => $loan->getCost(),
                'date' => $loan->getDate(),
                'active' => $loan->isActive(),
            ];
        }

        return $this->json($loanList);
    }
}
