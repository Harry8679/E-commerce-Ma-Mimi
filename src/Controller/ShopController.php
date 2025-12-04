<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/boutique', name: 'app_shop')]
    public function index(
        ProductRepository $productRepository, 
        CategoryRepository $categoryRepository,
        Request $request
    ): Response
    {
        // Récupérer le filtre de catégorie si présent
        $categoryId = $request->query->get('category');
        
        // Récupérer toutes les catégories actives
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);
        
        // Filtrer les produits par catégorie si nécessaire
        if ($categoryId) {
            $products = $productRepository->findBy([
                'isActive' => true,
                'category' => $categoryId
            ], ['name' => 'ASC']);
        } else {
            $products = $productRepository->findBy(['isActive' => true], ['name' => 'ASC']);
        }

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
        ]);
    }

    #[Route('/produit/{slug}', name: 'app_product_show')]
    public function show(Product $product): Response
    {
        // Vérifier que le produit est actif
        if (!$product->isActive()) {
            throw $this->createNotFoundException('Ce produit n\'est pas disponible');
        }

        // Récupérer des produits similaires (même catégorie)
        $relatedProducts = $product->getCategory()->getProducts()
            ->filter(fn($p) => $p->isActive() && $p->getId() !== $product->getId())
            ->slice(0, 4);

        return $this->render('shop/show.html.twig', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}