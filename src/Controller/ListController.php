<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Lists;
use App\Entity\Movies;
use App\Form\NewListType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// gestion de toutes les pages concernant les listes personnelles
class ListController extends AbstractController
{
    /**
     * @Route("/list", name="list")
     */
    // page d'accueil des listes personnelles
    public function listIndex()
    {
        // récupération des favoris et des listes de l'utilisateur
        $user = $this->getUser();
        $lists = $user->getLists();
        for ($i = 0; $i < count($lists); $i++)
        {
            if ($lists[$i]->getName() == 'Mes favoris')
            {
                $favorites = $lists[$i];
            break;
            }
        }

        // récupération du chemin url de l'image
        $images_url = array();
        for ($i = 0; $i < count($lists); $i++)
        {
            $movies = $lists[$i]->getMovies();
            if (count($movies) == 0)
            {
                $images_url[$i] = "none";
            }
            else
            {
                $movie = $movies[count($movies) - 1];
                $movie_id = $movie->getMovieId();
                $movie_url = 'https://api.themoviedb.org/3/movie/' . $movie_id . '?api_key=eb803997160f46de136642d8ee023920&language=fr-FR';
                $movie_curl = curl_init($movie_url);
                curl_setopt($movie_curl, CURLOPT_RETURNTRANSFER, true);
                $movie_response = curl_exec($movie_curl);
                curl_close($movie_curl);
                $movie_result = json_decode($movie_response, $assoc = TRUE);
                $images_url[$i] = "https://image.tmdb.org/t/p/w780" . $movie_result["poster_path"];
            }            
        }
        
        return $this->render('list/all.html.twig', [
            'lists' => $lists,
            'favorites' => $favorites,
            'images_url' => $images_url
        ]);
    }

    /**
     * @Route("/list/new", name="list_create")
     */
    // création d'une nouvelle liste
    public function createList(Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        $lists = $user->getLists();
        
        $list = new Lists();

        $form = $this->createForm(NewListType::class, $list);

        $form -> handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {          
            $new_title = $form->get('name')->getData();

            $already = $user->getLists()->filter(function(Lists $listcollection) use(&$new_title) {
                return $listcollection->getName() == $new_title;
            });

            if (count($already) > 0)
            {
                $this->addFlash('success', 'Votre liste n\'a pas pu être créée car vous avez déjà une liste portant ce nom');
                return $this->redirectToRoute('list_create');
            }

            $list->setCanErase(true);
            $list->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($list);
            $entityManager->flush();

            $this->addFlash('success', 'Votre liste "' . $list->getName() . '" a bien été créée');
            
            return $this->redirectToRoute('list'); 
        }

        return $this->render('list/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    /**
     * @Route("/list/favorites", name="list_favorites")
     */
    public function favorites()
    {
        $user = $this->getUser();
        $lists = $user->getLists();
        for ($i = 0; $i < count($lists); $i++)
        {
            if ($lists[$i]->getName() == 'Mes favoris')
            {
                $favorites = $lists[$i];
            break;
            }
        }
        $movies_in_favorites = $favorites->getMovies();
        
        $movies_results = array();
        foreach ($movies_in_favorites as $movie_in_favorites)
        {
            $movie_url = 'https://api.themoviedb.org/3/movie/' . $movie_in_favorites->getMovieId() . '?api_key=eb803997160f46de136642d8ee023920&language=fr-FR';
            $movie_curl = curl_init($movie_url);
            curl_setopt($movie_curl, CURLOPT_RETURNTRANSFER, true);
            $movie_response = curl_exec($movie_curl);
            curl_close($movie_curl);
            $movie_results = json_decode($movie_response, $assoc = TRUE);
            array_push($movies_results, $movie_results);
        }

        return $this->render('list/favorites.html.twig', [
            'movies' => $movies_results,
            'favorites' => $favorites
        ]);
    }

    /**
     * @Route("/list/favorites/delete/{id}", name="list_delete_favorite")
     */
    public function deleteFromFavorite($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getName() == 'Mes favoris')
            {
                $favorites = $list[$i];
            break;
            }
        } 

        $movies_in_favorites = $favorites->getMovies();
        for ($i = 0; $i < count($movies_in_favorites); $i++)
        {
            if ($movies_in_favorites[$i]->getMovieId() == $id)
            {
                $movie = $movies_in_favorites[$i];
            break;
            }
        }

        $favorites->setLastUpdate(new \DateTime());        
        $favorites->removeMovie($movie);
        $entityManager = $this->getDoctrine()->getManager();    
        $entityManager->flush();    

        $this->addFlash('success', 'Film supprimé de vos favoris');

        return $this->redirect("/list/favorites");        
    }

    /**
     * @Route("/list/favorites/share/{id}", name="list_favorites_share")
     */
    public function shareFavorites($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $list[$i]->setIsShared(true);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Vos favoris sont désormais partagés');

        return $this->redirect("/list/favorites");        
    }

    /**
     * @Route("/list/favorites/unshare/{id}", name="list_favorites_unshare")
     */
    public function unshareFavorites($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $list[$i]->setIsShared(false);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Vos favoris sont désormais privés');

        return $this->redirect("/list/favorites");        
    }

    /**
     * @Route("/list/delete/{id}", name="list_delete")
     */
    public function deleteList($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $name = $list[$i]->getName();
                $user->removeList($list[$i]);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Votre liste "' . $name . '" a bien été supprimée');

        return $this->redirect("/list");        
    }

    /**
     * @Route("/list/share/{id}", name="list_share")
     */
    public function shareList($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $list[$i]->setIsShared(true);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Votre liste "' . $list[$i]->getName() . '" est désormais partagée');

        return $this->redirect("/list");        
    }

    /**
     * @Route("/list/unshare/{id}", name="list_unshare")
     */
    public function unshareList($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $list[$i]->setIsShared(false);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Votre liste "' . $list[$i]->getName() . '" est désormais privée');

        return $this->redirect("/list");        
    }

    /**
     * @Route("/list/edit/{id}", name="list_edit")
     */
    public function showList($id)
    {
        $user = $this->getUser();
        $lists = $user->getLists();
        for ($i = 0; $i < count($lists); $i++)
        {
            if ($lists[$i]->getId() == $id)
            {
                $list = $lists[$i];
            break;
            }
        }
        $movies_in_list = $list->getMovies();
        
        $movies_results = array();
        foreach ($movies_in_list as $movie_in_list)
        {
            $movie_url = 'https://api.themoviedb.org/3/movie/' . $movie_in_list->getMovieId() . '?api_key=eb803997160f46de136642d8ee023920&language=fr-FR';
            $movie_curl = curl_init($movie_url);
            curl_setopt($movie_curl, CURLOPT_RETURNTRANSFER, true);
            $movie_response = curl_exec($movie_curl);
            curl_close($movie_curl);
            $movie_results = json_decode($movie_response, $assoc = TRUE);
            array_push($movies_results, $movie_results);
        }

        return $this->render('list/edit.html.twig', [
            'movies' => $movies_results,
            'list' => $list
        ]);
    }    

    /**
     * @Route("/list/edit/share/{id}", name="list_edit_share")
     */
    public function editShareList($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $list[$i]->setIsShared(true);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Votre liste "' . $list[$i]->getName() . '" est désormais partagée');

        return $this->redirect("/list/edit/" . $id);        
    }

    /**
     * @Route("/list/edit/unshare/{id}", name="list_edit_unshare")
     */
    public function editUnshareList($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getId() == $id)
            {
                $list[$i]->setIsShared(false);
                $manager->flush();
                break;
            }
        } 

        $this->addFlash('success', 'Votre liste "' . $list[$i]->getName() . '" est désormais privée');

        return $this->redirect("/list/edit/" . $id);        
    }

    /**
     * @Route("/list/edit/delete/{movieId}/{listId}", name="list_edit_delete")
     */
    public function deleteFromEditList($movieId, $listId, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $lists = $user->getLists();
        for ($i = 0; $i < count($lists); $i++)
        {
            if ($lists[$i]->getId() == $listId)
            {
                $list = $lists[$i];
            break;
            }
        } 

        $movies_in_list = $list->getMovies();
        for ($i = 0; $i < count($movies_in_list); $i++)
        {
            if ($movies_in_list[$i]->getMovieId() == $movieId)
            {
                $movie = $movies_in_list[$i];
            break;
            }
        }

        $list->setLastUpdate(new \DateTime());        
        $list->removeMovie($movie);
        $entityManager = $this->getDoctrine()->getManager();    
        $entityManager->flush();   
        
        $this->addFlash('success', 'Film supprimé de la liste "' . $list->getName() . '"');

        return $this->redirect("/list/edit/" . $listId);        
    }
    /**
     * @Route("/list/modify/{id}", name="modify_list")
     */
    public function editList($id,Request $request, EntityManagerInterface $manager)
    {
        
        $user = $this->getUser();
        $lists = $user->getLists();

        
        for ($i = 0; $i < count($lists); $i++)
        {
            if ($lists[$i]->getId() == $id)
            {
                $list = $lists[$i];
                
            }
        }
        $name = $list->getName();
        $desc = $list->getDescription();
        dump($desc);
        
        $form = $this->createFormBuilder($list)
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('is_shared', CheckboxType::class, ['required' => false])
            ->add('save', SubmitType::class)
            ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_title = $form->get('name')->getData();

            $already = $user->getLists()->filter(function(Lists $listcollection) use(&$new_title) {
                return $listcollection->getName() == $new_title;
            });

            if (count($already) > 1)
            {
                $this->addFlash('success', 'Votre liste n\'a pas pu être modifiée car vous avez déjà une liste portant ce nom');
                return $this->redirect("/list/edit/".$id );
            }

            $manager->persist($list);
            $manager->flush();

            $this->addFlash('success', 'Modifications de votre liste effectuées');

            return $this->redirect("/list/edit/".$id );
        }

        return $this->render('list/modify_list.html.twig', [
            'form' => $form->createView()
        ]);        
    }
}
