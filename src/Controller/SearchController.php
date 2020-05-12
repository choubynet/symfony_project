<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;
use App\Entity\Movies;
use App\Entity\Lists;

class SearchController extends AbstractController
{
    function transform_str($str)
    {       
        $str = preg_replace('#Ç#', 'C', $str);
        $str = preg_replace('#ç#', 'c', $str);
        $str = preg_replace('#è|é|ê|ë#', 'e', $str);
        $str = preg_replace('#È|É|Ê|Ë#', 'E', $str);
        $str = preg_replace('#à|á|â|ã|ä|å#', 'a', $str);
        $str = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $str);
        $str = preg_replace('#ì|í|î|ï#', 'i', $str);
        $str = preg_replace('#Ì|Í|Î|Ï#', 'I', $str);
        $str = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $str);
        $str = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $str);
        $str = preg_replace('#ù|ú|û|ü#', 'u', $str);
        $str = preg_replace('#Ù|Ú|Û|Ü#', 'U', $str);
        $str = preg_replace('#ý|ÿ#', 'y', $str);
        $str = preg_replace('#Ý#', 'Y', $str);
        $str = str_replace(' ', '+', $str);
        $str = preg_replace("#[^a-zA-Z0-9]#", "+", $str); 
        while( strpos($str, '++') !== FALSE )
        {
            $str=str_replace('++', '+', $str);
        }
        $str = strtolower($str);
        return $str;
    }

    /**
     * @Route("/results", name="global_results")
     */
    public function results(Request $request)
    {
        $search = $request->get('search');
        $search_by = $request->get('search_by');

        $search = $this->transform_str($search);
        if ($search_by == 'release')
        {
            $request_url = 'https://api.themoviedb.org/3/discover/movie?api_key=eb803997160f46de136642d8ee023920&language=fr-FR&sort_by=popularity.desc&include_adult=false&include_video=false&page=1&primary_release_year=' . $search;
        }
        else if ($search_by == 'type')
        {
            $searchby_requesturl = 'https://api.themoviedb.org/3/genre/movie/list?api_key=eb803997160f46de136642d8ee023920&language=fr-FR';
            $searchby_curl = curl_init($searchby_requesturl);
            curl_setopt($searchby_curl, CURLOPT_RETURNTRANSFER, true);
            $searchby_response = curl_exec($searchby_curl);
            curl_close($searchby_curl);
            $searchby_results = json_decode($searchby_response, $assoc = TRUE)["genres"];
            $genre_id = -1;
            for ($i = 0; $i < count($searchby_results); $i++)
            {
                if (stristr($searchby_results[$i]["name"], $search) != false)
                {
                    $genre_id = $searchby_results[$i]["id"];
                break;
                }
            }

            $request_url = 'https://api.themoviedb.org/3/discover/movie?api_key=eb803997160f46de136642d8ee023920&language=fr-FR&sort_by=popularity.desc&include_adult=false&include_video=false&page=1&with_genres=' . $genre_id;
        }
        else if ($search_by == 'actor')
        {
            $searchby_requesturl = 'https://api.themoviedb.org/3/search/person?api_key=eb803997160f46de136642d8ee023920&query=' . $search;
            $searchby_curl = curl_init($searchby_requesturl);
            curl_setopt($searchby_curl, CURLOPT_RETURNTRANSFER, true);
            $searchby_response = curl_exec($searchby_curl);
            curl_close($searchby_curl);
            $searchby_results = json_decode($searchby_response, $assoc = TRUE)["results"];            
            if ($searchby_results == null)
            {
                $request_url = 'https://api.themoviedb.org/3/discover/movie?api_key=eb803997160f46de136642d8ee023920&language=fr-FR&sort_by=popularity.desc&include_adult=false&include_video=false&page=1&with_cast=aaaaaaaaaa';                
            }
            else
            {
                $request_url = 'https://api.themoviedb.org/3/discover/movie?api_key=eb803997160f46de136642d8ee023920&language=fr-FR&sort_by=popularity.desc&include_adult=false&include_video=false&page=1&with_cast=' . $searchby_results[0]["id"];
            }            
        }
        else
        {
            $request_url = 'https://api.themoviedb.org/3/search/movie?api_key=eb803997160f46de136642d8ee023920&query=' . $search . '&language=fr-FR';
        }
        
        $curl = curl_init($request_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        $number_of_results = json_decode($response, $assoc = TRUE)["total_results"];
        $results = json_decode($response, $assoc = TRUE)["results"];

        $is_in_fav = array();
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
            for ($i = 0; $i < count($results); $i++)
            {
                $is_in_fav[$i] = 0;
                for ($j = 0; $j < count($movies_in_favorites); $j++)
                {
                    if ($movies_in_favorites[$j]->getMovieId() == $results[$i]["id"])
                    {
                        $is_in_fav[$i] = 1;
                    break;
                    }
                }
            }                   
        }

        return $this->render('search/results.html.twig', [
            'controller_name' => 'SearchController',
            'results' => $results,
            'nbresults' => $number_of_results,
            'is_in_fav' => $is_in_fav,
            'search' => $search,
            'search_by' => $search_by
        ]);
    }

    /**
     * @Route("/search/addfavorite/{id}/{search}/{search_by}", name="search_add_favorite")
     */
    public function addToFavorite($id, $search, $search_by, EntityManagerInterface $manager)
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

        return $this->redirectToRoute('global_results', [
            'search' => $search,
            'search_by' => $search_by
        ]);
    }

    /**
     * @Route("/search/deletefavorite/{id}/{search}/{search_by}", name="search_delete_favorite")
     */
    public function deleteFromFavorite($id, $search, $search_by, EntityManagerInterface $manager)
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

        return $this->redirectToRoute('global_results', [
            'search' => $search,
            'search_by' => $search_by
        ]);        
    }
}
