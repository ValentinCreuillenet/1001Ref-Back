<?php

namespace App\Controller;

use App\Entity\Account;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;

class AccountController extends AbstractController
{ 
    /**
     * @Route("/sign-in", name="account", methods={"POST"})
     */
    public function createUser (Request $request): Response
    {

        $response = $this->json($request->toArray(),Response::HTTP_OK,[],[]);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        // On décode les données envoyées
        $form = $request->toArray();

        // on initialise le code de réponse
        $code = 409;

        $entityManager = $this->getDoctrine()->getManager();

        $user = $entityManager->getRepository(User::class)->findBy(['mail' => $form["mail"]]);
        
        // si l'utilisateur n'existe pas
        if($user == null){

            // On instancie un nouvel utilisateur
            $user = new User;
            // On hydrate l'objet user
            $user->hydrate($form);
        

            // on instancie un nouveau compte
            $account = new Account();
            // on hydrate l'objet account
            $account->hydrate($user);

            // On sauvegarde en base de donnée
            $entityManager->persist($account);
            $entityManager->flush();
            
            // on change le code de réponse
            $code = 201;
            $response = new Response('Ok', $code);

            // On retourne la confirmation
            return $response;

        }
        return $response;
    }


    /**
     * @Route("/getUsers", name="getUsers", methods={"GET"})
     */
    public function getUsers(): Response
    {
        $response = $this->getDoctrine()->getRepository(User::class)->findAll();
        ?>
        <pre>
            <?= var_dump($response);?>
        </pre>
        <?php
        return new Response();
    }
}