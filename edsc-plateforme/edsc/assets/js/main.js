// 1. Menu mobile
const burger = document.getElementById('burger');
const menu = document.getElementById('menu');
if (burger && menu) {
  burger.addEventListener('click', () => {
    const ouvert = menu.classList.toggle('ouvert');
    burger.setAttribute('aria-expanded', String(ouvert));
  });
}

// 2. Mode sombre
const themeToggle = document.getElementById('theme-toggle');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const savedTheme = localStorage.getItem('edsc-theme');
const applyTheme = (theme) => {
  const isDark = theme === 'dark';
  document.body.classList.toggle('theme-sombre', isDark);
  if (themeToggle) {
    themeToggle.setAttribute('aria-label', isDark ? 'Activer le mode clair' : 'Activer le mode sombre');
    themeToggle.setAttribute('aria-pressed', String(isDark));
  }
};
const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');
applyTheme(initialTheme);
if (themeToggle) {
  themeToggle.addEventListener('click', () => {
    const nextTheme = document.body.classList.contains('theme-sombre') ? 'light' : 'dark';
    localStorage.setItem('edsc-theme', nextTheme);
    applyTheme(nextTheme);
  });
}

// 3. Vérification du mot de passe avant envoi (la vraie vérification reste côté PHP)
const formulairesCompte = document.querySelectorAll('#form-compte, #form-inscription');
formulairesCompte.forEach((formulaire) => {
  formulaire.addEventListener('submit', (event) => {
    const mdp = formulaire.mot_de_passe.value;
    const conf = formulaire.confirmation.value;
    if (mdp !== conf) {
      event.preventDefault();
      alert('Les deux mots de passe ne sont pas identiques.');
    }
  });
});

