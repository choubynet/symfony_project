<?php

namespace App\Controller;

use App\Entity\Lists;
use App\Entity\Movies;
use App\Entity\Users;
use App\Form\NewListType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// gestion des pages d'affichage des films et de l'ajout/supression à des listes
class MovieController extends AbstractController
{
    /**
     * @Route("/movie/{id}", name="movie")
     */
    // affichage de la fiche détaillée d'un film
    public function movie($id)
    {
        $list = array();
        $is_in_list = array();
        $is_in_fav = 0;
        $user = $this->getUser();
        if ($user != null)
        {
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
                    $is_in_fav = 1;
                break;
                }
            }
            for ($i = 0; $i < count($list); $i++)
            {
                $movies_in_list = $list[$i]->getMovies();
                $is_in_list[$i] = 0;
                for ($j = 0; $j < count($movies_in_list); $j++)
                {
                    if ($movies_in_list[$j]->getMovieId() == $id)
                    {
                        $is_in_list[$i] = 1;
                    }
                }
            }
        }

        $movie_url = 'https://api.themoviedb.org/3/movie/' . $id . '?api_key=eb803997160f46de136642d8ee023920&language=fr-FR';
        $movie_curl = curl_init($movie_url);
        curl_setopt($movie_curl, CURLOPT_RETURNTRANSFER, true);
        $movie_response = curl_exec($movie_curl);
        curl_close($movie_curl);
        $movie_results = json_decode($movie_response, $assoc = TRUE);

        $actors_url = 'https://api.themoviedb.org/3/movie/'.$id.'/credits?api_key=eb803997160f46de136642d8ee023920';
        $actors_curl = curl_init($actors_url);
        curl_setopt($actors_curl, CURLOPT_RETURNTRANSFER, true);
        $actors_response = curl_exec($actors_curl);
        curl_close($actors_curl);
        $actors_results = json_decode($actors_response, $assoc = TRUE)["cast"];
        $cast_count = count($actors_results);

        $crew_results = json_decode($actors_response, $assoc = TRUE)["crew"];
        $director = array();
        $writer = array();
        foreach ($crew_results as $element)
        {
            if ($element["job"] == "Screenplay" OR $element["job"] == "Writer")
            {
                array_push($writer, $element["name"]);
            }
            else if ($element["job"] == "Director")
            {
                array_push($director, $element["name"]);
            }
        }
        array_unique($writer);
        $director_count = count($director);
        $writer_count = count($writer);

        return $this->render('movie/card.html.twig', [
            'controller_name' => 'SearchController',
            'cast' => $actors_results,
            'actor_count' => $cast_count,
            'image_path' => $movie_results["poster_path"],
            'genres' => $movie_results["genres"],
            'title' => $movie_results["title"],
            'summary' => $movie_results["overview"],
            'country' => $movie_results["production_countries"],
            'release' => $movie_results["release_date"],
            'runtime' => $movie_results["runtime"],
            'director' => $director,
            'director_count' => $director_count,
            'writer' => $writer,
            'writer_count' => $writer_count,
            'id' => $movie_results["id"],
            'is_in_fav' => $is_in_fav,
            'lists' => $list,
            'is_in_list' => $is_in_list
        ]);
    }

    /**
     * @Route("/movie/addfavorite/{id}", name="movie_add_favorite")
     */
    public function addToFavorite($id, EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        $list = $user->getLists();
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->getName() == 'Mes favoris')
            {
                $favorite = $list[$i];
            break;
            }
        }        

        $favorite->setLastUpdate(new \DateTime());        
        $movie = new Movies();
        $movie->setMovieId($id);
        $movie->setList($favorite); 
        $entityManager = $this->getDoctrine()->getManager();        
        $entityManager->persist($movie);
        $entityManager->flush();  
        
        $this->addFlash('success', 'Film ajouté à vos favoris');

        return $this->redirect("/movie/%2B" . $id);        
    }

    /**
     * @Route("/movie/deletefavorite/{id}", name="movie_delete_favorite")
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

        return $this->redirect("/movie/%2B" . $id);        
    }

    /**
     * @Route("/movie/addlist/{movieId}/{listId}", name="movie_add_list")
     */
    public function addToList($movieId, $listId, EntityManagerInterface $manager)
    {
        if ($listId == 'new')
        {
            return $this->redirect("/movie/newlist/" . $movieId);
        }

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
        
        $already_in_list = false;
        $movies = $list->getMovies();
        for ($i = 0; $i < count($movies); $i++)
        {
            if ($movies[$i]->getmovieId() == $movieId)
            {
                $already_in_list = true;
                $movie = $movies[$i];
            break;
            }
        } 

        if ($already_in_list == true)
        {
            $list->setLastUpdate(new \DateTime());        
            $list->removeMovie($movie);
            $entityManager = $this->getDoctrine()->getManager();    
            $entityManager->flush();   

            $this->addFlash('success', 'Film supprimé de votre liste "' . $list->getName() . '"');

            return $this->redirect("/movie/" . $movieId);
        }
        else
        {
            $list->setLastUpdate(new \DateTime());        
            $movie = new Movies();
            $movie->setMovieId($movieId);
            $movie->setList($list); 
            $entityManager = $this->getDoctrine()->getManager();        
            $entityManager->persist($movie);
            $entityManager->flush();  

            $this->addFlash('success', 'Film ajouté à votre liste "' . $list->getName() . '"');
            
            return $this->redirect("/movie/" . $movieId);
        }     
    }

    /**
     * @Route("/movie/newlist/{movieId}", name="movie_create_list")
     */
    // création d'une nouvelle liste
    public function createList($movieId, Request $request, EntityManagerInterface $manager): Response
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
                return $this->redirect("/movie/newlist/" . $movieId);
            }

            $list->setCanErase(true);
            $list->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($list);
            $entityManager->flush();

            $this->addFlash('success', 'Votre liste "' . $list->getName() . '" a bien été créée');
            
            return $this->redirect("/movie/" . $movieId); 
        }

        return $this->render('movie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
