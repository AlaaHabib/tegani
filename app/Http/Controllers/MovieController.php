<?php

namespace App\Http\Controllers;

use External\Bar\Exceptions\ServiceUnavailableException as BarServiceUnavailableException;
use External\Bar\Movies\MovieService as BarMovieService;
use External\Baz\Exceptions\ServiceUnavailableException as BazServiceUnavailableException;
use External\Baz\Movies\MovieService as BazMovieService;
use External\Foo\Exceptions\ServiceUnavailableException;
use External\Foo\Movies\MovieService as FooMovieService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MovieController extends Controller
{
    public function getTitles()
    {
        try {
            // Use caching to store and retrieve titles
            $titles = Cache::remember('movie_titles', now()->addMinutes(60), function () {
                // Attempt to retrieve titles from Foo, Bar, and Baz systems
                $fooTitles = $this->getTitlesFromService(new FooMovieService());
                $barTitles = $this->getTitlesFromService(new BarMovieService());
                $bazTitles = $this->getTitlesFromService(new BazMovieService());

                // Combine and flatten the results
                return array_merge($fooTitles, $barTitles, $bazTitles);
            });

            return response()->json($titles);
        } catch (ServiceUnavailableException | BarServiceUnavailableException | BazServiceUnavailableException $exception) {
            // Log the exception
            Log::error('Error retrieving movie titles: ' . $exception->getMessage(), [
                'code' => $exception->getCode(),
                'trace' => $exception->getTrace(),
            ]);

            return response()->json(['status' => 'failure']);
        }
    }

    /**
     * Get titles from a MovieService with retry mechanism.
     *
     * @param MovieService $movieService
     * @param int $maxAttempts
     * @return array
     */
    private function getTitlesFromService($movieService, $maxAttempts = 3)
    {
        return retry($maxAttempts, function () use ($movieService) {

            $titles = $movieService->getTitles();
            if (is_array($titles)) {
                $titlesArray = Arr::get($titles, 'titles', $titles);
            
                if (is_array($titlesArray)) {
                    return $this->flattenTitles($titlesArray);
                }
            
                return $titlesArray;
            }
            
            // Default case: Return an empty array if $titles is not an array
            return [];
        }, 100);
    }
    /**
     * Get flat titles from array 
     *
     * @param $titlesArray
     * @return array
     */
    private function flattenTitles($titlesArray)
    {
        if ($this->isArrayOfArrays($titlesArray)) {
            return Arr::pluck($titlesArray, 'title');
        }
    
        return $titlesArray;
    }

    /**
     * Check if array contains multiple arrays
     *
     * @param $array
     * @return boolean
     */
    private function isArrayOfArrays($array)
    {
        return is_array($array) && count(array_filter($array, 'is_array')) === count($array);
    }

}
