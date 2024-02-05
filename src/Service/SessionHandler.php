<?php
namespace App\Service;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Cookie;

class SessionHandler
{
    public function createSession(string $username): Cookie
    {
        // $sessionId = Uuid::uuid4()->toString();
        $sessionId =  $username;
        return new Cookie('X-Session-ID', $sessionId, time() + 3600);
    }
}