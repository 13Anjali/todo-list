<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\Task;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use App\Service\SessionHandler;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class TaskService
{
    private ManagerRegistry $managerRegistry;
    private LoggerInterface $logger;
    private UserService $userService;
    private SessionHandler $session;

    public function __construct(ManagerRegistry $managerRegistry,UserService $userService, LoggerInterface $logger,SessionHandler $session)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
        $this->userService = $userService;
        $this->session = $session;
    }

    public function findTaskById(int $id): ?Task
    {
        $entityManager = $this->managerRegistry->getManager();
        $userRepository = $entityManager->getRepository(Task::class);

        return $userRepository->findOneBy(["id" => $id]);
    }

    public function findTaskByUsername(Request $request,string $username):?JsonResponse
    {
        $sessionId = $request->cookies->get('X-Session-ID');
        if (!$sessionId||$sessionId !== $username)
        {
            return new JsonResponse('Please login', Response::HTTP_UNAUTHORIZED);
        }
        $isComplete=$request->request->get("isComplete");
        if(!$this->userService->findAndValidateUserByUsername($username))
        {
            return new JsonResponse('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }
        $entityManager = $this->managerRegistry->getManager();
        $user = $entityManager->getRepository(Users::class)->findOneBy(["username"=>$username]);
        $tasks=$user->getTasks();
        $result=[];
        foreach ($tasks as $task) {
            if($task->isIsComplete()==$isComplete){
            $result[] = [
                'id' => $task->getId(),
                'description' => $task->getDescription()
            ];
        }
        }
        return new JsonResponse($result, Response::HTTP_OK);
    }

    public function addTask(Request $request, string $username): Response
    {
        $sessionId = $request->cookies->get('X-Session-ID');
        if (!$sessionId||$sessionId !== $username)
        {
            return new Response('Please login', Response::HTTP_UNAUTHORIZED);
        }
        $entityManager = $this->managerRegistry->getManager();
        $description=$request->request->get("description");
        $isComplete=$request->request->get("isComplete");
        $user = $this->userService->findAndValidateUserByUsername($username);

        if(!$user)
        {
            return new Response('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        $task = new Task();
        $task->setUsers($user);
        $task->setDescription($description);
        $task->setIsComplete($isComplete);

        $entityManager->persist($task);
        $entityManager->flush();

        return new Response('added new task!', 200);
    }

    public function updateTask(Request $request, string $username, int $id): Response
    {
        $sessionId = $request->cookies->get('X-Session-ID');
        if (!$sessionId||$sessionId !== $username)
        {
            return new Response('Please login', Response::HTTP_UNAUTHORIZED);
        }
        $entityManager = $this->managerRegistry->getManager();
        $description=$request->request->get("description");
        $isComplete=$request->request->get("isComplete");

        if(!$this->userService->findAndValidateUserByUsername($username))
        {
            return new Response('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        $task = $this->findTaskById($id);

        if ($task->isIsComplete()) {
            throw new \InvalidArgumentException('Completed tasks cannot be updated');
        }

        $this->logger->info('description' . $description);
        $task->setDescription($description);
        $task->setIsComplete($isComplete);

        $entityManager->persist($task);
        $entityManager->flush();

        return new Response('updated task!', Response::HTTP_OK);
    }

    public function deleteTask(Request $request,$username,$id): Response
    {
        $sessionId = $request->cookies->get('X-Session-ID');
        if (!$sessionId||$sessionId!==$username)
        {
            new Response('Please login', Response::HTTP_UNAUTHORIZED);
        }

        if(!$this->userService->findAndValidateUserByUsername($username))
        {
            return new Response('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }


        $task = $this->findTaskById($id);

        $entityManager = $this->managerRegistry->getManager();

        $entityManager->remove($task);
        $entityManager->flush();
        return new Response('task deleted',Response::HTTP_OK);
    }
}







// public function findTaskByUsername(Request $request, string $username): array
// {
//     $sessionId = $request->cookies->get('X-Session-ID');
//     if (!$sessionId || $sessionId !== $username) {
//         // You can redirect to a login page instead of rendering an error message
//         throw new AccessDeniedException('Please login');
//     }

//     $isComplete = $request->query->get("isComplete"); // Use query instead of request for GET parameters

//     if (!$this->userService->findAndValidateUserByUsername($username)) {
//         throw new AccessDeniedException('Invalid credentials');
//     }

//     $entityManager = $this->managerRegistry->getManager();
//     $user = $entityManager->getRepository(Users::class)->findOneBy(["username" => $username]);
//     $tasks = $user->getTasks();
//     $result = [];

//     foreach ($tasks as $task) {
//         if ($task->isIsComplete() == $isComplete) {
//             $result[] = [
//                 'id' => $task->getId(),
//                 'description' => $task->getDescription(),
//                 'isComplete'=>$task->isIsComplete()
//             ];
//         }
//     }

//     return $result;
// }

