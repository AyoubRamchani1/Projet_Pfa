<?php

class Movie {
    public $movie_id;
    public $title;
    public $overview;
    public $original_language;
    public $popularity;
    public $vote_average;
    public $genres;
    public $directeur;
    public $acteurs;

    public function __construct($movie_id,$title, $overview, $original_language, $popularity, $vote_average, $genres, $directeur, $acteurs) {
        $this->movie_id          = $movie_id;
        $this->title             = $title;
        $this->overview          = $overview;
        $this->original_language = $original_language;
        $this->popularity        = $popularity;
        $this->vote_average      = $vote_average;
        $this->genres            = $genres;
        $this->directeur         = $directeur;
        $this->acteurs           = $acteurs;
    }

}
?>