<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$orders = $em->getRepository(App\Entity\Order::class)->findAll();
echo "Total orders: " . count($orders) . "\n";
foreach($orders as $o) {
    echo "Order: " . $o->getId() . " - " . $o->getCustomerName() . "\n";
}
