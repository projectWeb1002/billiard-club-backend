<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\UserInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiOrderController extends AbstractController
{
    private $serializerInterface;

    public function __construct(SerializerInterface $serializerInterface)
    {
        $this->serializerInterface = $serializerInterface;
    }

    // post /api/order (json) => (json)
    #[Route("/api/order", methods: ['POST'], name: 'app_api_order')]
    public function order(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['error' => 'No data'], 400);
        }

        $order = new Order();

        $user = $entityManager->getRepository(UserInfo::class)->find($data['userInfoId']);
        $order->setCustomer($user);
        if (isset($data['status'])) {
            $order->setStatus($data['status']);
        }
        $order->setCreatedAt(new \DateTime());

        $entityManager->persist($order);
        $entityManager->flush();
        return $this->json(['orderId' => $order->getId()], 201, ['Content-Type' => 'application/json']);
    }

    // post /api/order-detail (json) => (json)
    #[Route("/api/order-detail", methods: ['POST'], name: 'app_api_order_detail')]
    public function orderDetail(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['error' => 'No data'], 400);
        }

        $order = $entityManager->getRepository(Order::class)->find($data['orderId']);

        $orderDetail = new OrderDetail();
        $orderDetail->setOrderId($order);
        $product = $entityManager->getRepository(Product::class)->findOneBy(['id' => $data['productId']]);
        if (null == $product) {
            return $this->json(['error' => 'Product not found'], 400);
        }
        $orderDetail->setProduct($data['productId']);
        $orderDetail->setAmount($data['amount']);
        $entityManager->persist($orderDetail);
        if ($product->getAmount() - $data['amount'] < 0) {
            return $this->json(['error' => 'Not enough product'], 400);
        }
        $product->setAmount($product->getAmount() - $data['amount']);

        $entityManager->persist($product);
        $entityManager->flush();
        return $this->json('Added info', 201, ['Content-Type' => 'application/json']);
    }

    // POST /api/order/detail (json) => (json)
    #[Route("/api/order/detail", methods: ['POST'], name: 'app_api_get-order')]
    public function getOrder(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['error' => 'No data'], 400);
        }
        $order = $entityManager->getRepository(Order::class)->find($data['orderId']);
        $orderDetail = $entityManager->getRepository(OrderDetail::class)->findAll($data['orderId']);
        $orderDetailResponse = [];
        foreach ($orderDetail  as $key => $value) {
            $product = $entityManager->getRepository(Product::class)->findOneBy(['id' => $value->getProduct()]);
            $orderDetailResponse[] = [
                'productId' => $value->getProduct(),
                'productName' => $product->getName(),
                'productPrice' => $product->getPrice(),
                'amount' => $value->getAmount(),
            ];
        }
        $response = $this->serializerInterface->serialize(
            [
                'status' => $order->getStatus(),
                'date' => $order->getCreatedAt(),
                'orderDetail' => $orderDetailResponse
            ],
            'json'
        );

        return new Response($response, 200, ['Content-Type' => 'application/json']);
    }

    // patch /api/order-update-status (json) => (json)
    #[Route("/api/order-update-status", methods: ['PATCH'], name: 'app_api_order_update_status')]
    public function orderUpdateStatus(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['error' => 'No data'], 400);
        }

        $order = $entityManager->getRepository(Order::class)->find($data['orderId']);
        $order->setStatus($data['status']);
        $entityManager->persist($order);
        $entityManager->flush();
        return $this->json('Added info', 201, ['Content-Type' => 'application/json']);
    }
}
