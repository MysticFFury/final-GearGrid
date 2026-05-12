<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;

class PcBuildCompatibilityService
{
    /** @var list<string> */
    public const SLOT_ORDER = ['cpu', 'motherboard', 'memory', 'gpu', 'psu', 'storage'];

    private const PLATFORM_OVERHEAD_W = 90;
    private const PSU_HEADROOM_FACTOR = 1.18;

    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @return array<string, list<array{id: string, label: string, price: float}>>
     */
    public function getCatalogByRole(): array
    {
        $catalog = [];
        foreach (self::SLOT_ORDER as $role) {
            $catalog[$role] = [];
        }

        $products = $this->productRepository->findAll();

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }
            if (($product->getQuantity() ?? 0) <= 0) {
                continue;
            }

            $role = $this->guessRole($product);
            if ($role === null) {
                continue;
            }

            $catalog[$role][] = [
                'id' => (string) $product->getId(),
                'label' => $product->getName() ?? 'Unnamed product',
                'price' => (float) ($product->getPrice() ?? 0),
            ];
        }

        foreach (self::SLOT_ORDER as $role) {
            usort(
                $catalog[$role],
                static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label'])
            );
        }

        return $catalog;
    }

    /**
     * @param array<string, mixed> $selectionByRole
     * @return array<string, mixed>
     */
    public function analyzeSelection(array $selectionByRole): array
    {
        $catalog = $this->getCatalogByRole();
        $byRoleAndId = [];
        foreach (self::SLOT_ORDER as $role) {
            $byRoleAndId[$role] = [];
            foreach ($catalog[$role] as $part) {
                $byRoleAndId[$role][(string) $part['id']] = $part;
            }
        }

        $resolved = [];
        foreach (self::SLOT_ORDER as $role) {
            $id = isset($selectionByRole[$role]) ? (string) $selectionByRole[$role] : '';
            $resolved[$role] = $id !== '' ? ($byRoleAndId[$role][$id] ?? null) : null;
        }

        $issues = [];
        $highlightRoles = [];

        // Basic structure checks with current schema (name/category based only).
        foreach (self::SLOT_ORDER as $role) {
            if ($resolved[$role] === null) {
                $issues[] = [
                    'code' => 'MISSING_COMPONENT',
                    'message' => sprintf('Select a %s to continue compatibility checking.', ucfirst($role)),
                    'roles' => [$role],
                ];
                $highlightRoles[] = $role;
            }
        }

        $estimatedLoadWatts = $this->estimateLoadWatts($resolved);
        $psuRecommended = (int) ceil($estimatedLoadWatts * self::PSU_HEADROOM_FACTOR);
        $psuPart = $resolved['psu'];
        if ($psuPart !== null) {
            $psuWatts = $this->extractWattage((string) ($psuPart['label'] ?? ''));
            if ($psuWatts !== null && $psuWatts < $psuRecommended) {
                $issues[] = [
                    'code' => 'PSU_UNDERSIZED',
                    'message' => sprintf(
                        'Selected PSU looks low: recommended around %dW, selected model appears to be %dW.',
                        $psuRecommended,
                        $psuWatts
                    ),
                    'roles' => ['psu'],
                ];
                $highlightRoles[] = 'psu';
            }
        }

        $highlightRoles = array_values(array_unique($highlightRoles));
        $complete = !in_array(null, $resolved, true);
        $compatible = $complete && !$this->hasHardError($issues);

        return [
            'status' => $compatible ? 'compatible' : ($complete ? 'warning' : 'partial'),
            'label' => $compatible ? 'Compatible' : ($complete ? 'Check warnings' : 'Incomplete build'),
            'compatible' => $compatible,
            'complete' => $complete,
            'issues' => $issues,
            'highlightRoles' => $highlightRoles,
            'resolved' => $resolved,
            'estimatedLoadWatts' => $estimatedLoadWatts,
            'recommendedPsuWatts' => $psuRecommended,
            'totalPrice' => $this->sumPrice($resolved),
        ];
    }

    private function hasHardError(array $issues): bool
    {
        foreach ($issues as $issue) {
            if (($issue['code'] ?? '') !== 'MISSING_COMPONENT') {
                return true;
            }
        }

        return false;
    }

    private function sumPrice(array $resolved): float
    {
        $total = 0.0;
        foreach ($resolved as $part) {
            if (is_array($part)) {
                $total += (float) ($part['price'] ?? 0);
            }
        }

        return $total;
    }

    private function estimateLoadWatts(array $resolved): int
    {
        $cpuName = (string) (($resolved['cpu']['label'] ?? ''));
        $gpuName = (string) (($resolved['gpu']['label'] ?? ''));

        $cpu = $this->extractCpuTdp($cpuName);
        $gpu = $this->extractGpuTdp($gpuName);

        return $cpu + $gpu + self::PLATFORM_OVERHEAD_W;
    }

    private function extractCpuTdp(string $name): int
    {
        $n = strtolower($name);
        if (str_contains($n, 'i9') || str_contains($n, 'ryzen 9')) {
            return 125;
        }
        if (str_contains($n, 'i7') || str_contains($n, 'ryzen 7')) {
            return 105;
        }
        if (str_contains($n, 'i5') || str_contains($n, 'ryzen 5')) {
            return 65;
        }

        return 65;
    }

    private function extractGpuTdp(string $name): int
    {
        $n = strtolower($name);
        if (str_contains($n, '4090') || str_contains($n, '7900')) {
            return 350;
        }
        if (str_contains($n, '4080') || str_contains($n, '7800')) {
            return 320;
        }
        if (str_contains($n, '4070') || str_contains($n, '7700')) {
            return 250;
        }
        if (str_contains($n, '4060') || str_contains($n, '7600')) {
            return 180;
        }

        return 220;
    }

    private function extractWattage(string $name): ?int
    {
        if (preg_match('/(\d{3,4})\s*w/i', $name, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    private function guessRole(Product $product): ?string
    {
        $name = strtolower((string) $product->getName());
        $cat = strtolower((string) ($product->getCategory()?->getName() ?? ''));
        $hay = $name.' '.$cat;

        if ($this->containsAny($hay, ['cpu', 'processor', 'ryzen', 'intel core', 'core i'])) {
            return 'cpu';
        }
        if ($this->containsAny($hay, ['motherboard', 'mainboard', 'b650', 'z790', 'x670', 'h610'])) {
            return 'motherboard';
        }
        if ($this->containsAny($hay, ['ram', 'memory', 'ddr4', 'ddr5'])) {
            return 'memory';
        }
        if ($this->containsAny($hay, ['gpu', 'graphics', 'rtx', 'gtx', 'radeon'])) {
            return 'gpu';
        }
        if ($this->containsAny($hay, ['psu', 'power supply', '80+', 'watt'])) {
            return 'psu';
        }
        if ($this->containsAny($hay, ['ssd', 'hdd', 'nvme', 'storage', 'm.2'])) {
            return 'storage';
        }

        return null;
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}

