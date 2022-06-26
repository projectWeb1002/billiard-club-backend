<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiUserController extends AbstractController
{
    private $serializerInterface;

    public function __construct(SerializerInterface $serializerInterface)
    {
        $this->serializerInterface = $serializerInterface;
    }
    // post /api/register (json) => (json)
    #[Route('/api/register', methods: ['POST'], name: 'app_api_register')]
    public function register(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['error' => 'No data'], 400);
        }


        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($user) {
            return $this->json(['error' => 'User already exists'], 400);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setPassword($data['password']);

        $secretKeyList = [
            'manager' => 'manager secret key',
            'admin' => 'admin secret key',
        ];
        $rule = 'customer';
        if (isset($data['secretKey'])) {
            foreach ($secretKeyList as $key => $value) {
                if ($data['secretKey'] === $value) {
                    $rule = $key;
                    break;
                }
            }
        }

        $user->setRule($rule);
        $entityManager->persist($user);
        $entityManager->flush();

        $userId = $user->getId();

        $response = $this->serializerInterface->serialize(
            [
                'status' => 'success',
                'userId' => $userId
            ],
            'json'
        );

        return new Response($response, 200, ['Content-Type' => 'application/json']);
    }

    // post /api/register/info (json) => (json)
    #[Route("/api/register-info", methods: ['POST'], name: 'app_api_register_info')]
    public function registerInfo(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $userInfo = new UserInfo();
        $userInfo->setName($data['name']);
        $userInfo->setEmail($data['email']);
        $userInfo->setPhone($data['phone']);
        $userInfo->setAddress($data['address']);
        $userInfo->setDateOfBirth(new \DateTime($data['dayOfBirth']));
        $user = $entityManager->getRepository(User::class)->find($data['user-id']);
        $userInfo->setUser($user);

        $entityManager->persist($userInfo);
        $entityManager->flush();
        return $this->json('Added info', 201, ['Content-Type' => 'application/json']);
    }

    // post /api/user-info => (json)
    #[Route("/api/user-info", methods: ['POST'], name: 'app_api_users')]
    public function getUserInfo(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $entityManager->getRepository(User::class)->find($data['userId']);

        $userInfo = [];

        if (null != $user->getUserInfos()) {
            foreach ($user->getUserInfos() as $key => $value) {
                $userInfo[] = [
                    'userInfoId' => $value->getId(),
                    'name' => $value->getName(),
                    'email' => $value->getEmail(),
                    'phone' => $value->getPhone(),
                    'address' => $value->getAddress(),
                    'dayOfBirth' => $value->getDateOfBirth()->format('Y-m-d'),
                ];
            }
        }

        $response = $this->serializerInterface->serialize(
            [
                'status' => 'success',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'rule' => $user->getRule(),
                    'userInfo' => $userInfo
                ]
            ],
            'json'
        );
        return new Response($response, 200, ['Content-Type' => 'application/json']);
    }

    // post /api/login (json) => (json)
    #[Route("/api/login", methods: ['POST'], name: 'app_api_login')]
    public function login(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if (null == $user) {
            return $this->json(['error' => 'User not found'], 400);
        }

        $response = $this->serializerInterface->serialize(
            [
                'status' => 'success',
                'userId' => $user->getId(),
                'password' => $user->getPassword()
            ],
            'json'
        );
        return new Response($response, 200, ['Content-Type' => 'application/json']);
    }

    // patch /api/update-account (json) => (json)
    #[Route("/api/update-account", methods: ['PATCH'], name: 'app_api_update_account_status')]
    public function updateAccountStatus(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $entityManager->getRepository(User::class)->find($data['userId']);
        if (null == $user) {
            return $this->json(['error' => 'User not found'], 400);
        }
        if (isset($data['rule'])) $user->setRule($data['rule']);
        if (isset($data['status'])) $user->setStatus($data['status']);
        if (isset($data['password'])) $user->setPassword($data['password']);

        $entityManager->persist($user);
        $entityManager->flush();
        return $this->json('Account status changed', 200, ['Content-Type' => 'application/json']);
    }

    // post /api/get-order (json) => (json)]
    #[Route("/api/get-user-order", methods: ['POST'], name: 'app_api_get_user-order')]
    public function getOrder(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $entityManager->getRepository(User::class)->find($data['userId']);
        if (null == $user) {
            return $this->json(['error' => 'User not found'], 400);
        }

        $userInfos = $user->getUserInfos();
        $orderList = [];

        foreach ($userInfos as $userInfo) {
            $order = $userInfo->getOrders();
            foreach ($order as $value) {
                $orderList[] = [
                    'orderId' => $value->getId(),
                    'orderDate' => $value->getCreatedAt(),
                    'orderStatus' => $value->getStatus(),
                ];
            }
        }

        $response = $this->serializerInterface->serialize(
            [
                'status' => 'success',
                'order' => $orderList
            ],
            'json'
        );
        return new Response($response, 200, ['Content-Type' => 'application/json']);
    }

    // get /api/get-all-user => (json)
    #[Route("/api/admin/get-all-user", methods: ['GET'], name: 'app_api_get_all_user')]
    public function getAllUser(EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findAll();
        $userList = [];
        foreach ($user as $value) {
            $userList[] = [
                'userId' => $value->getId(),
                'username' => $value->getUsername(),
                'rule' => $value->getRule(),
                'status' => $value->getStatus(),
            ];
        }
        $response = $this->serializerInterface->serialize(
            [
                'status' => 'success',
                'user' => $userList
            ],
            'json'
        );

        return new Response($response, 200, ['Content-Type' => 'application/json']);
    }
}
