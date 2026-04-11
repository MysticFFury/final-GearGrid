<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, LogService $logService): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCustomer = $form->get('customer')->getData();
            if ($selectedCustomer instanceof User) {
                $order->setCustomerName($selectedCustomer->getName());
            }
            
            $order->setCreatedBy($this->getUser());
            $entityManager->persist($order);
            $entityManager->flush();

            // LOG THE ACTION
            $logService->log('CREATE', 'Order', "Created order #{$order->getId()} for {$order->getCustomerName()}");

            $this->addFlash('success', 'Order has been created successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, UserRepository $userRepository, LogService $logService): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        
        if ($order->getCustomerName()) {
            $customer = $userRepository->findOneBy(['name' => $order->getCustomerName()]);
            if ($customer) {
                $form->get('customer')->setData($customer);
            }
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCustomer = $form->get('customer')->getData();
            if ($selectedCustomer instanceof User) {
                $order->setCustomerName($selectedCustomer->getName());
            }
            // Keep existing customer name if no new customer is selected
            elseif (!$order->getCustomerName()) {
                // If no customer name exists, set a default
                $order->setCustomerName('Guest Customer');
            }
            
            $entityManager->flush();

            // LOG THE ACTION
            $logService->log('UPDATE', 'Order', "Updated order #{$order->getId()}");

            $this->addFlash('success', 'Order #' . $order->getId() . ' has been updated successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager, LogService $logService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $entityManager->remove($order);
            $entityManager->flush();

            // LOG THE ACTION
            $logService->log('DELETE', 'Order', "Deleted order #{$orderId}");

            $this->addFlash('success', 'Order #' . $orderId . ' has been deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}