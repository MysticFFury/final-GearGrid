<?php

namespace App\Controller;

use App\Service\PcBuildCompatibilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CustomerPcBuilderController extends AbstractController
{
    #[Route('/customer-pc-builder', name: 'app_customer_pc_builder', methods: ['GET'])]
    public function index(PcBuildCompatibilityService $pcBuild): Response
    {
        return $this->render('customer/pc_builder.html.twig', [
            'catalog' => $pcBuild->getCatalogByRole(),
            'slotOrder' => PcBuildCompatibilityService::SLOT_ORDER,
        ]);
    }

    #[Route('/customer-pc-builder/check', name: 'app_customer_pc_builder_check', methods: ['POST'])]
    public function check(Request $request, PcBuildCompatibilityService $pcBuild): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $selection = is_array($payload) && isset($payload['selection']) && is_array($payload['selection'])
            ? $payload['selection']
            : [];

        return $this->json($pcBuild->analyzeSelection($selection));
    }
}

