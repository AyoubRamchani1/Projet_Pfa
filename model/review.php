<?php

class Review {

    public $id;
    public $movie_id;
    public $movie_title;
    public $user_name;
    public $note;
    public $comment;
    public $created_at;
    public $updated_at;

    public function __construct($movie_id, $movie_title, $user_name, $note, $comment) {
        $this->movie_id    = $movie_id;
        $this->movie_title = $movie_title;
        $this->user_name   = $user_name;
        $this->note        = $note;
        $this->comment     = $comment;
    }

}
?>