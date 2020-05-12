<?php

namespace App\Controller;

use App\Entity\Lists;
use App\Entity\Movies;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// classe gérant tout ce qui touche à la page d'accueil et les listes partagées
class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    // génération de la page d'accueil et des listes partagées
    public function index()
    {        
        // récupération de toutes les listes partagées à partir de la plus récente
        $repository_lists = $this->getDoctrine()
        ->getRepository(Lists::class);        
        $query_lists = $repository_lists->createQueryBuilder('list')
        ->where('list.is_shared = 1')
        ->orderBy('list.last_update', 'DESC')
        ->getQuery();
        $lists = $query_lists->getResult();

        // récupération du chemin url de l'image du dernier film ajouté à la liste
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

        return $this->render('home/index.html.twig', [
            'lists' => $lists,
            'images_url' => $images_url
        ]);
    }

    // création de la barre de recherche de films
    public function searchBar($search, $search_by)
    {        
        // construction du formulaire
        $form = $this->createFormBuilder(null)
            ->add('search', TextType::class)
            ->add('isAttending', ChoiceType::class, [
                'choices'  => [
                    'Film' => 'movie',
                    'Acteur' => 'actor',
                    'Genre'  => 'type',
                    'Date'  => 'release',
                ]
            ])
            ->getForm();

        return $this->render('home/searchBar.html.twig', [
            'form' => $form->createView(),
            'search' => $search,
            'search_by' => $search_by
        ]);
    }

    /**
     * @Route("/search", name="handle_search")
     */
    // récupération des données de la barre de recherche et redirection
    public function handleSearch(Request $request)
    {
        // récupération des données saisies
        $search = $request->request->get('form')['search'];
        $search_by = $request->request->get('form')['isAttending'];

        return $this->redirectToRoute('global_results', [
            'search' => $search,
            'search_by' => $search_by
        ]);
    }

    /**
     * @Route("/shared/{id}", name="list_shared")
     */
    // affichage d'une liste partagée
    public function sharedList($id)
    {
        // récupération de la liste demandée
        $repository_lists = $this->getDoctrine()
        ->getRepository(Lists::class);        
        $query_lists = $repository_lists->createQueryBuilder('list')
        ->where('list.id = ' . $id)
        ->getQuery();
        $list = $query_lists->getResult()[0];

        // récupération de tous les films de la liste et de leurs infos
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

        return $this->render('home/shared.html.twig', [
            'movies' => $movies_results,
            'list' => $list
        ]);
    }
}
