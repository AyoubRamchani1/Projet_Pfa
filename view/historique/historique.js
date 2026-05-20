document.addEventListener("DOMContentLoaded", () => {
    console.log("Historique chargé");

    const cards = document.querySelectorAll(".card");

    cards.forEach(card => {
        card.addEventListener("click", () => {
            card.classList.toggle("active");
        });
    });
});