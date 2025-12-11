<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
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
        $user = $this->getUser();
        
        // Staff can only see their own orders, admin can see all
        if ($this->isGranted('ROLE_ADMIN')) {
            $orders = $orderRepository->findAll();
        } else {
            $orders = $orderRepository->findBy(['createdBy' => $user]);
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creator
            $order->setCreatedBy($this->getUser());

            $entityManager->persist($order);
            $entityManager->flush();

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
        // Staff can only view their own orders, admin can view all
        if (!$this->isGranted('ROLE_ADMIN') && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own orders.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Check if staff can only edit their own records
        if (!$this->isGranted('ROLE_ADMIN') && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own orders.');
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Order #' . $order->getId() . ' has been updated successfully!');

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Check if staff can only delete their own records
        if (!$this->isGranted('ROLE_ADMIN') && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own orders.');
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order #' . $orderId . ' has been deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
