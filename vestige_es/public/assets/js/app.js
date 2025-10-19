
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.tarjeta, .step').forEach((el, i) => {
    el.style.opacity = 0;
    el.style.transform = 'translateY(8px)';
    setTimeout(() => {
      el.style.transition = 'opacity .4s ease, transform .4s ease';
      el.style.opacity = 1;
      el.style.transform = 'translateY(0)';
    }, 80 * i);
  });
});