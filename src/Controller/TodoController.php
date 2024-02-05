<?php

namespace App\Controller;

use App\Service\UserService;
use App\Service\TaskService;
use App\Service\SessionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class TodoController extends AbstractController
{

    private UserService $userService;
    private TaskService $taskService;
    private LoggerInterface $logger;
    private SessionHandler $session;

    public function __construct(UserService $userService, SessionHandler $session, TaskService $taskService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->taskService = $taskService;
        $this->session = $session;
        $this->logger = $logger;
    }

    #[Route('/register', name: 'app_todo_register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        return $this->userService->createUser($request);
    }

    #[Route('/login', name: 'app_todo_login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        return $this->userService->loginUser($request);
    }

    #[Route('/logout',name:'app_todo_logout',methods:['POST'])]
    public function logout(Request $request):Response
    {
        return $this->userService->logout($request);
    }

    #[Route('/{username}/task', name:'app_todo_add_task',methods:['POST'])]
    public function addTask(Request $request, string $username): Response
    {
            return $this->taskService->addTask($request, $username);
    }
    
    #[Route('/{username}/task/{id}', name:'app_todo_update_task',methods:['POST'])]
    public function updateTask(Request $request, string $username, int $id): Response
    {
        return $this->taskService->updateTask($request, $username, $id);
    }

    #[Route('/{username}/task/{id}', name:'app_todo_delete_task',methods:['DELETE'])]
    public function deleteTask(Request $request,string $username, int $id): Response
    {
        return $this->taskService->deleteTask($request,$username,$id);
    }

    #[Route('/{username}/task', name: 'app_todo_show_task', methods: ['GET'])]
    public function showTask(Request $request, string $username): Response
    {
        return new Response($this->taskService->findTaskByUsername($request, $username));
    }


    

}