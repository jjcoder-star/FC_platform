document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('.founder-card').forEach(card => {
    card.addEventListener('mouseenter', () => card.style.transform = "scale(1.03)");
    card.addEventListener('mouseleave', () => card.style.transform = "scale(1)");
    card.style.transition = "all 0.3s ease";
  });
});
