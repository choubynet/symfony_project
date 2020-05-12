<?php

namespace App\Controller;

use App\Entity\Lists;
use App\Entity\Movies;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/stats", name="stats")
     */
    // affichage des statistiques des favoris
    public function stats()
    {
        // récupération de toutes les listes de favoris
        $repository_lists = $this->getDoctrine()
        ->getRepository(Lists::class);        
        $query_lists = $repository_lists->createQueryBuilder('list')
        ->where('list.name = :fav')
        ->setParameter('fav', 'Mes favoris')
        ->getQuery();
        $lists = $query_lists->getResult();

        // récupération de tous les films des listes de favoris et classement par nombre d'apparition
        $favorites_movies_ranked = array();
        foreach ($lists as $list)
        {
            $movies = $list->getMovies();
            foreach ($movies as $movie)
            {
                if (array_key_exists($movie->getMovieId(), $favorites_movies_ranked))
                {
                    $favorites_movies_ranked[$movie->getMovieId()]++;
                }
                else
                {
                    $favorites_movies_ranked[$movie->getMovieId()] = 1;
                }
            }
        }
        arsort($favorites_movies_ranked);

        // récupération des infos des films
        $movies_details = array();
        foreach ($favorites_movies_ranked as $movie_id => $count)
        {
            $movie_url = 'https://api.themoviedb.org/3/movie/' . $movie_id . '?api_key=eb803997160f46de136642d8ee023920&language=fr-FR';
            $movie_curl = curl_init($movie_url);
            curl_setopt($movie_curl, CURLOPT_RETURNTRANSFER, true);
            $movie_response = curl_exec($movie_curl);
            curl_close($movie_curl);
            $movie_results = json_decode($movie_response, $assoc = TRUE);
            array_push($movies_details, $movie_results);
        }

        return $this->render('admin/stats.html.twig', [
            'controller_name' => 'AdminController',
            'movies_in_favorites' => $favorites_movies_ranked,
            'movies_details' => $movies_details
        ]);
    }
}
