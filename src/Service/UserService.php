<?php

namespace App\Service;
use App\Entity\Users;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use App\Service\SessionHandler;


class UserService
{
    private ManagerRegistry $managerRegistry;
    private LoggerInterface $logger;
    private SessionHandler $session;

    public function __construct(ManagerRegistry $managerRegistry,LoggerInterface $logger,SessionHandler $session)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger=$logger;
        $this->session = $session;
    }

    public function findAndValidateUserByUsername(string $username): ?Users
    {
        $entityManager = $this->managerRegistry->getManager();
        $userRepository = $entityManager->getRepository(Users::class);

        $user=$userRepository->findOneBy(["username" => $username]);
        return $user;
    }

    public function findAndValidateUserByUsernameAndPassword(string $username,string $password):?bool
    {
        $entityManager = $this->managerRegistry->getManager();
        $userRepository = $entityManager->getRepository(Users::class);

        $user=$userRepository->findOneBy(["username" => $username]);
        return $user&&$user->getPassword()==$password;
    }

    public function createUser(Request $request): Response
    {
        $username=$request->request->get("username");
        $password=$request->request->get("password");

        $entityManager = $this->managerRegistry->getManager();

        $existingUser = $entityManager->getRepository(Users::class)->findOneBy(['username' => $username]);

        if ($existingUser) {
            return new Response('User already exists',Response::HTTP_CONFLICT);
        }

        $user = new Users();
        $user->setUsername($username);
        $user->setPassword($password);

        $entityManager->persist($user);
        $entityManager->flush();

        return new Response('User created',Response::HTTP_CREATED);
    }

    public function loginUser(Request $request):Response
    {
        $username = $request->get('username');
        $password = $request->get('password');

        if(!$this->findAndValidateUserByUsernameAndPassword($username,$password))
        {
            return new Response('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }
        $cookie = $this->session->createSession($username);
        $response = new Response('Login successful for user', Response::HTTP_OK);
        $response->headers->setCookie($cookie);
        return $response;
    }

    public function logout(Request $request): Response
    {
        $username=$request->request->get("username");
        $sessionId = $request->cookies->get('X-Session-ID');
        $this->logger->info('logMessage'.$sessionId);
        if (!$sessionId)
        {
            return new Response('Please login', Response::HTTP_UNAUTHORIZED);
        }
        if($sessionId===$username){
            $response = new Response('User logged out');
            $response->headers->clearCookie('X-Session-ID');
            return $response;
        }
        return new Response('Invalid username');
    }
}
