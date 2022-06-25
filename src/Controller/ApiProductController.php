<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\UserInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiProductController extends AbstractController
{
    private $serializerInterface;

    public function __construct(SerializerInterface $serializerInterface)
    {
        $this->serializerInterface = $serializerInterface;
    }

    // post /api/add-product (json) => (json)
    #[Route('/api/add-product', methods: ['POST'], name: 'app_api_add_product')]
    public function addProduct(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(
                ['error' => 'No data'],
                400
            );
        }

        $product = new Product();
        if (isset($data['name'])) $product->setName($data['name']);
        if (isset($data['price'])) $product->setPrice($data['price']);
        if (isset($data['amount'])) $product->setAmount($data['amount']);
        if (isset($data['details'])) $product->setDetails($data['details']);
        if (isset($data['image'])) $product->setImage($data['image']);
        if (isset($data['category'])) $product->setCategory($data['category']);

        $entityManager->persist($product);
        $entityManager->flush();
        return $this->json(['productId' => $product->getId()], 201, ['Content-Type' => 'application/json']);
    }

    // patch /api/update-product (json) => (json)
    #[Route('/api/update-product', methods: ['PATCH'], name: 'app_api_update_product')]
    public function updateProduct(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(
                ['error' => 'No data'],
                400
            );
        }

        if (!isset($data['productId'])) {
            return $this->json(
                ['error' => 'No productId'],
                400
            );
        }

        $product = $entityManager->getRepository(Product::class)->find($data['productId']);
        if (isset($data['name'])) $product->setName($data['name']);
        if (isset($data['price'])) $product->setPrice($data['price']);
        if (isset($data['amount'])) $product->setAmount($data['amount']);
        if (isset($data['details'])) $product->setDetails($data['details']);
        if (isset($data['image'])) $product->setImage($data['image']);
        if (isset($data['category'])) $product->setCategory($data['category']);

        $entityManager->persist($product);
        $entityManager->flush();
        return $this->json('Updated', 200, ['Content-Type' => 'application/json']);
    }

    // delete /api/delete-product (json) => (json)
    #[Route('/api/delete-product', methods: ['DELETE'], name: 'app_api_delete_product')]
    public function deleteProduct(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(
                ['error' => 'No data'],
                400
            );
        }

        $product = $entityManager->getRepository(Product::class)->find($data['productId']);
        $entityManager->remove($product);
        $entityManager->flush();
        return $this->json('Deleted product', 200, ['Content-Type' => 'application/json']);
    }

    // post /api/get-product (json) => (json)
    #[Route('/api/get-product', methods: ['POST'], name: 'app_api_get_product')]
    public function getProduct(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(
                ['error' => 'No data'],
                400
            );
        }

        $product = $entityManager->getRepository(Product::class)->find($data['productId']);
        return $this->json($product, 200, ['Content-Type' => 'application/json']);
    }

    // post /api/get-products (json) => (json)
    #[Route('/api/get-products', methods: ['POST'], name: 'app_api_get_products')]
    public function getProducts(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // if (empty($data)) {
        //     return $this->json(
        //         ['error' => 'No data'],
        //         400
        //     );
        // }

        $products = $entityManager->getRepository(Product::class)->findAll();

        $response = [];
        foreach ($products as $product) {
            if ($product->getCategory() == $data['category']) {
                $response[] = [
                    'productId' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'amount' => $product->getAmount(),
                    'image' => $product->getImage(),
                    // 'category' => $product->getCategory()
                ];
            }
        }

        if (empty($response)) {
            return $this->json(
                ['error' => 'Not found products'],
                400
            );
        }

        return $this->json($response, 200, ['Content-Type' => 'application/json']);
    }
}
