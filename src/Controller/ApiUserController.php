<?php

namespace App\Controller;

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
        $user = $entityManager->getRepository(User::class)->findAll($data['userId']);

        $userInfo = [];

        foreach ($user as $key => $value) {
            if (null != $value->getUserInfos()) {
                foreach ($value->getUserInfos() as $key => $value) {
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
        }

        $response = $this->serializerInterface->serialize(
            [
                'status' => 'success',
                'user' => [
                    'id' => $user[0]->getId(),
                    'username' => $user[0]->getUsername(),
                    'rule' => $user[0]->getRule(),
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

    // put /api/forgot-password (json) => (json)
    #[Route("/api/forgot-password", methods: ['PUT'], name: 'app_api_forgot_password')]
    public function forgotPassword(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if (null == $user) {
            return $this->json(['error' => 'User not found'], 400);
        }
        $user->setPassword($data['password']);
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->json('Password changed', 200, ['Content-Type' => 'application/json']);
    }
}
