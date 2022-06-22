<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
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

        $user = $entityManager->getRepository(User::class)->find($data['userId']);
        $order->getCustomer($user);

        $entityManager->persist($order);
        $entityManager->flush();
        return $this->json('Added info', 201, ['Content-Type' => 'application/json']);
    }
}
