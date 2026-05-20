const starsEl = document.getElementById('stars');
for (let i = 0; i < 40; i++) {
  const s = document.createElement('div');
  s.className = 'star';
  s.style.cssText = `
    left:${Math.random()*100}%;
    top:${Math.random()*100}%;
    --dur:${2+Math.random()*4}s;
    --delay:${Math.random()*5}s;
  `;
  starsEl.appendChild(s);
}
document.addEventListener("DOMContentLoaded", () => {
    console.log("Favoris chargé");

    const cards = document.querySelectorAll(".card");

    cards.forEach(card => {
        card.addEventListener("click", () => {
            card.classList.toggle("active");
        });
    });
});