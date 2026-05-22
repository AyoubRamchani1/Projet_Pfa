# Test Suite

Run the PHP test suite from the project root:

```bash
php tests/run_tests.php
```

This suite covers:
- Recherche intelligente (mots simples, mots multiples, mots absents, tags/overview)
- Recommandations TF-IDF (profil vide, profil actif, exclusion des films vus)
- TMDB cache (poster/trailer fallback, cache local)
- Base de données (auto-incrémentation `movie_id`, favoris, moyenne de notes)
- Interface statique (liens de cards, barre de recherche live, bouton Regarder)

Requirements:
- PHP CLI installed
- Projet database accessible via `configuration_base.php`
