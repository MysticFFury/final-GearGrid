<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, LogService $logService): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('products_directory'), $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: '.$e->getMessage());
                }
                $product->setImage($newFilename);
            }

            $product->setCreatedBy($this->getUser());
            $entityManager->persist($product);
            $entityManager->flush();

            // LOG THE ACTION
            $logService->log('CREATE', 'Product', "Created new product: {$product->getName()}");

            $this->addFlash('success', '✅ Product added successfully!');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger, LogService $logService): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        $oldImage = $product->getImage();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                if ($oldImage) {
                    $oldPath = $this->getParameter('products_directory').'/'.$oldImage;
                    if (file_exists($oldPath)) { @unlink($oldPath); }
                }
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('products_directory'), $newFilename);
                } catch (FileException $e) { }

                $product->setImage($newFilename);
            } else {
                $product->setImage($oldImage);
            }

            $entityManager->flush();

            // LOG THE ACTION
            $logService->log('UPDATE', 'Product', "Updated product: {$product->getName()}");

            $this->addFlash('success', '✅ Product updated successfully!');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            if ($product->getImage()) {
                $path = $this->getParameter('products_directory').'/'.$product->getImage();
                if (file_exists($path)) { @unlink($path); }
            }

            $productName = $product->getName();
            $this->entityManager->remove($product);
            $this->entityManager->flush();
            $entityManager->remove($product);
            $entityManager->flush();

            // LOG THE ACTION
            $logService->log('DELETE', 'Product', "Deleted product: {$productName}");

            $this->addFlash('success', '🗑️ Product deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index');
    }
}