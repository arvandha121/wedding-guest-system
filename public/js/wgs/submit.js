export default function submit() {
  document.addEventListener('submit', e => {
    const b = e.submitter || e.target.querySelector('[type=submit]');
    if (b) setTimeout(() => b.classList.add('is-loading'), 0);
  });
  addEventListener('pageshow', () => document.querySelectorAll('.is-loading').forEach(b => b.classList.remove('is-loading')));
}
