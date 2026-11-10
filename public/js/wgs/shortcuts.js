export default function shortcuts() {
  document.addEventListener('keydown', e => {
    const s = document.getElementById('search');
    if (e.key === '/' && s && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) { e.preventDefault(); s.focus(); }
  });
}
