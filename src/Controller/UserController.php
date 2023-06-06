<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user')]
class UserController extends AbstractController
{
    #[Route('/')]
    public function getUserInfo(): Response {
        $me = $this->getUser();

        return $this->json([
            'user' => [
                'name' => $me->getName(),
                'function' => $me->getFunction(),
            ],
        ]);
    }
}