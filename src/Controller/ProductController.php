<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $user = $this->getUser();
        
        // Staff can only see their own products, admin can see all
        if ($this->isGranted('ROLE_ADMIN')) {
            $products = $productRepository->findAll();
        } else {
            $products = $productRepository->findBy(['createdBy' => $user]);
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
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
                    $imageFile->move(
                        $this->getParameter('products_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: '.$e->getMessage());
                }

                $product->setImage($newFilename);
            }

            // Set the creator
            $product->setCreatedBy($this->getUser());

            $entityManager->persist($product);
            $entityManager->flush();

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
        // Staff can only view their own products, admin can view all
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own products.');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Check if staff can only edit their own records
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own products.');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        $oldImage = $product->getImage();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Delete old image if exists
                if ($oldImage) {
                    $oldPath = $this->getParameter('products_directory').'/'.$oldImage;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('products_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: '.$e->getMessage());
                }

                $product->setImage($newFilename);
            } else {
                // Keep old image if none uploaded
                $product->setImage($oldImage);
            }

            $entityManager->flush();

            $this->addFlash('success', '✅ Product updated successfully!');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Check if staff can only delete their own records
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own products.');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            // Delete image if exists
            if ($product->getImage()) {
                $path = $this->getParameter('products_directory').'/'.$product->getImage();
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', '🗑️ Product deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index');
    }
}
