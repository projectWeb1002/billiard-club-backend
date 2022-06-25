<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetail;
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
        $order->getCustomer($user);

        $entityManager->persist($order);
        $entityManager->flush();
        return $this->json('Added info', 201, ['Content-Type' => 'application/json']);
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
        $orderDetail->setProduct($data['productId']);
        $orderDetail->setAmount($data['amount']);

        $entityManager->persist($order);
        $entityManager->flush();
        return $this->json('Added info', 201, ['Content-Type' => 'application/json']);
    }
}
