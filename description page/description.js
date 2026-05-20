// Navigation
function navigate(page, btn) {
  window.location.href = page;
  setActive(btn);
}

// Active button
function setActive(btn) {
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

// Scroll (si tu l'utilises pour tes films)
function scroll(rowId) {
  const row = document.getElementById(rowId);
  if (row) {
    row.scrollBy({ left: 500, behavior: 'smooth' });
  }
}

// Card hover (optionnel mais OK)
document.querySelectorAll('.card').forEach(card => {
  card.addEventListener('mouseenter', () => {
    card.style.zIndex = '10';
  });
  card.addEventListener('mouseleave', () => {
    card.style.zIndex = '';
  });
});
const sampleMovies = {
  "game-of-thrones": {
    title: "Game of Thrones",
    year: "2019",
    genre: "Action, Drame, Fantastique",
    duration: "55 min",
    rating: "9.3",
    director: "David Benioff & D. B. Weiss",
    language: "Anglais / Français",
    cast: "Emilia Clarke, Kit Harington, Peter Dinklage",
    poster: "https://i.pinimg.com/736x/e6/8c/26/e68c26589b230a464ad6f7b204a2fd63.jpg",
    badge: "Série",
    description: "Le pouvoir est une force qui change des royaumes entiers...",
    suggestions: [
      {
        title: "La Casa de Papel",
        year: "2017",
        poster: "https://images.rtl.fr/~c/770v513/funradio/www/1473231-la-casa-de-papel.jpg",
      },
      {
        title: "Stranger Things",
        year: "2016",
        poster: "https://dnm.nflximg.net/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABQEq9p8KKh4cDljpOPBvnme-VOdV0kO-1mqfBIHlUbqQHGNOpERWh3cjE_J6UitiD-6dryVOoz1HyEp_ab_vT4popBXwkpea8YFU.jpg?r=e8a",
      },
      {
        title: "Extraction",
        year: "2020",
        poster: "https://image.tmdb.org/t/p/w500/wlfDxbGEsW58vGhFljKkcR5IxDj.jpg",
      },
    ],
  },

  "extraction": {
    title: "Extraction",
    year: "2020",
    genre: "Action, Thriller",
    duration: "116 min",
    rating: "7.0",
    director: "Sam Hargrave",
    language: "Anglais",
    cast: "Chris Hemsworth, Rudhraksh Jaiswal, Randeep Hooda",
    poster: "https://image.tmdb.org/t/p/w500/wlfDxbGEsW58vGhFljKkcR5IxDj.jpg",
    badge: "Netflix",
    description: "Un mercenaire est engagé pour sauver le fils d'un baron du crime...",
    suggestions: [
      {
        title: "Game of Thrones",
        year: "2019",
        poster: "https://i.pinimg.com/736x/e6/8c/26/e68c26589b230a464ad6f7b204a2fd63.jpg",
      },
      {
        title: "Stranger Things",
        year: "2016",
        poster: "https://dnm.nflximg.net/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABQEq9p8KKh4cDljpOPBvnme-VOdV0kO-1mqfBIHlUbqQHGNOpERWh3cjE_J6UitiD-6dryVOoz1HyEp_ab_vT4popBXwkpea8YFU.jpg?r=e8a",
      },
      {
        title: "La Casa de Papel",
        year: "2017",
        poster: "https://images.rtl.fr/~c/770v513/funradio/www/1473231-la-casa-de-papel.jpg",
      },
    ],
  },
};

const comments = [
  {
    author: "Sara",
    text: "Super film ! L'action est intense et la narration est bien rythmée.",
  },
  {
    author: "Youssef",
    text: "J'adore le style visuel et les personnages sont très forts.",
  },
];

const defaultMovie = "game-of-thrones";

function getQueryParam(name) {
  return new URLSearchParams(window.location.search).get(name);
}

function showToast(message) {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(window.toastTimeout);
  window.toastTimeout = setTimeout(() => {
    toast.classList.remove("show");
  }, 2500);
}

function renderMovie(movieKey) {
  const movie = sampleMovies[movieKey] || sampleMovies[defaultMovie];
  document.getElementById("movieTitle").textContent = movie.title;
  document.getElementById("movieSubtitle").textContent = `${movie.year} • ${movie.genre} • ${movie.duration}`;
  document.getElementById("movieRating").textContent = movie.rating;
  document.getElementById("movieDesc").textContent = movie.description;
  document.getElementById("movieDirector").textContent = movie.director;
  document.getElementById("movieGenres").textContent = movie.genre;
  document.getElementById("movieLanguage").textContent = movie.language;
  document.getElementById("movieCast").textContent = movie.cast;
  document.getElementById("moviePoster").src = movie.poster;
  document.getElementById("moviePoster").alt = `Affiche de ${movie.title}`;
  document.getElementById("movieBadge").textContent = movie.badge;
  renderSuggestions(movie.suggestions);
}

function renderComments() {
  const list = document.getElementById("commentsList");
  list.innerHTML = comments
    .map(
      (comment) => `
      <div class="comment-card">
        <strong>${comment.author}</strong>
        <p>${comment.text}</p>
      </div>
    `
    )
    .join("");
  document.getElementById("commentsCount").textContent = `${comments.length} commentaire${comments.length > 1 ? "s" : ""}`;
}

function renderSuggestions(suggestions) {
  const container = document.getElementById("suggestionsList");
  container.innerHTML = suggestions
    .map(
      (movie) => `
      <div class="suggestion-card">
        <img src="${movie.poster}" alt="Affiche ${movie.title}" />
        <div>
          <strong>${movie.title}</strong>
          <span>${movie.year}</span>
        </div>
      </div>
    `
    )
    .join("");
}

function initPage() {
  const selectedMovie = getQueryParam("movie") || defaultMovie;
  renderMovie(selectedMovie);
  renderComments();

  document.getElementById("commentForm").addEventListener("submit", (event) => {
    event.preventDefault();
    const textarea = document.getElementById("commentText");
    const text = textarea.value.trim();
    if (!text) {
      showToast("Veuillez écrire un commentaire avant d'envoyer.");
      return;
    }
    comments.unshift({ author: "Anonyme", text });
    textarea.value = "";
    renderComments();
    showToast("Commentaire ajouté !");
  });

  document.getElementById("watchBtn").addEventListener("click", () => {
    const title = document.getElementById("movieTitle").textContent;
    showToast(`Lecture de ${title}...`);
    window.open(`https://www.youtube.com/results?search_query=${encodeURIComponent(title + " bande annonce")}`, "_blank");
  });

  document.getElementById("commentBtn").addEventListener("click", () => {
    document.getElementById("commentText").focus();
  });

  document.getElementById("settingsBtn").addEventListener("click", () => {
    showToast("Paramètres non disponibles pour le moment.");
  });
}


// URL params
const urlParams = new URLSearchParams(window.location.search);
const user = urlParams.get('user');

if (user) {
  showMessage(decodeURIComponent(user), 'success');
}