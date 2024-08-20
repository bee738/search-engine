const API_BASE = 'https://yts.mx/api/v2/';

// Search for movies based on user input
function searchMovies() {
  const searchTerm = document.getElementById('search-term').value;
  const url = `${API_BASE}list_movies.json?query_term=${encodeURIComponent(searchTerm)}&limit=10`;

  fetch(url)
    .then(response => response.json())
    .then(data => displayMovies(data.data.movies))
    .catch(error => console.error('Error:', error));
}

// Display list of movies
function displayMovies(movies) {
  const movieList = document.getElementById('movie-list');
  movieList.innerHTML = '';

  if (movies && movies.length > 0) {
    movies.forEach(movie => {
      const movieCard = `
        <div class="col-md-4 mb-4">
          <div class="card bg-secondary text-light">
            <img src="${movie.medium_cover_image}" class="card-img-top" alt="${movie.title}">
            <div class="card-body">
              <h5 class="card-title">${movie.title} (${movie.year})</h5>
              <p class="card-text">Rating: ${movie.rating}/10</p>
              <button class="btn btn-info" onclick="getMovieDetails(${movie.id})">Details</button>
            </div>
          </div>
        </div>
      `;
      movieList.innerHTML += movieCard;
    });
  } else {
    movieList.innerHTML = '<p>No movies found.</p>';
  }
}

// Fetch and display movie details
function getMovieDetails(movieId) {
  const url = `${API_BASE}movie_details.json?movie_id=${movieId}&with_images=true&with_cast=true`;

  fetch(url)
    .then(response => response.json())
    .then(data => displayMovieDetails(data.data.movie))
    .catch(error => console.error('Error:', error));
}

// Display detailed information about the selected movie
function displayMovieDetails(movie) {
  const movieDetails = document.getElementById('movie-details');
  
  const movieHTML = `
    <div class="card bg-secondary text-light">
      <div class="row g-0">
        <div class="col-md-4">
          <img src="${movie.large_cover_image}" class="img-fluid rounded-start" alt="${movie.title}">
        </div>
        <div class="col-md-8">
          <div class="card-body">
            <h5 class="card-title">${movie.title} (${movie.year})</h5>
            <p class="card-text">${movie.description_full}</p>
            <p><strong>Rating:</strong> ${movie.rating}/10</p>
            <p><strong>Runtime:</strong> ${movie.runtime} minutes</p>
            <p><strong>Genre:</strong> ${movie.genres.join(', ')}</p>
            <p><strong>Cast:</strong> ${movie.cast.map(actor => actor.name).join(', ')}</p>
            <a href="${movie.url}" class="btn btn-primary" target="_blank">View on YTS</a>
          </div>
        </div>
      </div>
    </div>
  `;

  movieDetails.innerHTML = movieHTML;

  // Fetch and display movie suggestions
  getMovieSuggestions(movie.id);
}

// Fetch and display related movie suggestions
function getMovieSuggestions(movieId) {
  const url = `${API_BASE}movie_suggestions.json?movie_id=${movieId}`;

  fetch(url)
    .then(response => response.json())
    .then(data => displayMovieSuggestions(data.data.movies))
    .catch(error => console.error('Error:', error));
}

// Display suggested movies
function displayMovieSuggestions(movies) {
  const movieDetails = document.getElementById('movie-details');

  let suggestionsHTML = '<h5 class="mt-4">Related Movies</h5><div class="row">';
  
  movies.forEach(movie => {
    suggestionsHTML += `
      <div class="col-md-3 mb-3">
        <div class="card bg-dark text-light">
          <img src="${movie.medium_cover_image}" class="card-img-top" alt="${movie.title}">
          <div class="card-body">
            <h6 class="card-title">${movie.title}</h6>
          </div>
        </div>
      </div>
    `;
  });

  suggestionsHTML += '</div>';
  
  movieDetails.innerHTML += suggestionsHTML;
}
