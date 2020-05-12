<?php
namespace App\Controller;

use App\Entity\Users;
use App\Entity\Lists;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription", name="security_registration")
     */
    public function registration(Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder): Response
    {   
        $user = new Users();
        $form = $this->createForm(RegistrationType::class, $user);
        
        $form -> handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {            
            $favorite = new Lists();
            $favorite->setName('Mes favoris');
            $favorite->setCanErase(false);
            $favorite->setIsShared(false);
            $favorite->setLastUpdate(new \DateTime());
            $favorite->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->persist($favorite);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé, vous pouvez maintenant vous connecter');
            
            return $this->redirectToRoute('security_login');            
        }

        return $this->render('security/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/login", name="security_login")
     */
    public function login()
    {
        return $this->render('security/login.html.twig');
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    public function logout()
    {
        // controller can be blank: it will never be executed!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }    
}