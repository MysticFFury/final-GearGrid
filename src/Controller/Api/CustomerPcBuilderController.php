<?php

namespace App\Controller\Api;

use App\Service\PcBuildCompatibilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerPcBuilderController extends AbstractController
{
    #[Route('/api/customer/pc-builder/catalog', name: 'api_customer_pc_builder_catalog', methods: ['GET'])]
    public function catalog(PcBuildCompatibilityService $pcBuild): JsonResponse
    {
        return $this->json([
            'catalog' => $pcBuild->getCatalogByRole(),
            'slotOrder' => PcBuildCompatibilityService::SLOT_ORDER,
        ]);
    }
}
