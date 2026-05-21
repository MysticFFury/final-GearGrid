<?php

namespace App\Controller;

use App\Entity\Log;
use App\Form\LogType;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/log')]
#[IsGranted('ROLE_ADMIN')]
final class LogController extends AbstractController
{
    // List all logs
    #[Route(name: 'app_log_index', methods: ['GET'])]
    public function index(Request $request, LogRepository $logRepository): Response
    {
        $userFilter = $request->query->get('user');
        $actionFilter = $request->query->get('action');
        $fromInput = $request->query->get('from');
        $toInput = $request->query->get('to');

        $from = $fromInput ? (new \DateTimeImmutable($fromInput))->setTime(0, 0, 0) : null;
        $to = $toInput ? (new \DateTimeImmutable($toInput))->setTime(23, 59, 59) : null;

        return $this->render('log/index.html.twig', [
            'logs' => $logRepository->findFiltered($userFilter, $actionFilter, $from, $to),
            'actions' => $logRepository->findDistinctActions(),
            'users' => $logRepository->findDistinctUsers(),
            'filters' => [
                'user' => $userFilter,
                'action' => $actionFilter,
                'from' => $fromInput,
                'to' => $toInput,
            ],
        ]);
    }

    // Create new log
    #[Route('/new', name: 'app_log_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        throw $this->createAccessDeniedException('Logs are read-only.');
    }

    // Show log
    #[Route('/{id}', name: 'app_log_show', methods: ['GET'])]
    public function show(Log $log): Response
    {
        return $this->render('log/show.html.twig', [
            'log' => $log,
        ]);
    }

    // Edit log
    #[Route('/{id}/edit', name: 'app_log_edit', methods: ['GET', 'POST'])]
    public function edit(): Response
    {
        throw $this->createAccessDeniedException('Logs are read-only.');
    }

    // Delete log
    #[Route('/{id}', name: 'app_log_delete', methods: ['POST'])]
    public function delete(): Response
    {
        throw $this->createAccessDeniedException('Logs are read-only.');
    }

    // Toggle status
    #[Route('/{id}/toggle', name: 'app_log_toggle', methods: ['POST'])]
    public function toggle(): Response
    {
        throw $this->createAccessDeniedException('Logs are read-only.');
    }
}
