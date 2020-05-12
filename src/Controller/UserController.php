<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\EditUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
    /**
     * @Route("/user/edit", name="edit_user")
     */
     public function EditUser(Request $request, EntityManagerInterface $manager )
     {
        $user = $this->getUser();
        $form = $this->createForm(EditUserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $myData = $form->getData();
            $user->setFirstName($myData['first_name']);
            $user->setLastName($myData['last_name']);
            $user->setUsername($myData['username']);
            $user->setEmail($myData['email']);
            $user->setPassword($myData['password']);
            $manager->flush();

            $this->addFlash('success', 'Modifications effectuées');

            return $this->redirectToRoute('home');
        }
         return $this->render('user/edit_user.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/user/delet", name="delet_user")
     */
    public function deletUser(EntityManagerInterface $manager)
    {
        $user = $user = $this->getUser();

        $this->get('security.token_storage')->setToken(null);
        $this->get('session')->invalidate();
        
        $manager->remove($user);
        $manager->flush();

        $this->addFlash('success', 'Compte supprimé');

        return $this->redirectToRoute('home');
    }
}
