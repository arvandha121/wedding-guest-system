export default function avatar() {
  const nm = document.querySelector('header .sm\\:block p');
  if (!nm) return;
  const a = document.createElement('span');
  a.className = 'wgs-avatar';
  a.textContent = nm.textContent.trim().charAt(0).toUpperCase();
  nm.closest('.flex').prepend(a);
}
