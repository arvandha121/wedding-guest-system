export default function alerts() {
  document.querySelectorAll('[role=status],[role=alert]').forEach(el => {
    const x = document.createElement('button');
    x.type = 'button'; x.className = 'wgs-x'; x.innerHTML = '&times;'; x.setAttribute('aria-label', 'Tutup');
    const close = () => { el.classList.add('wgs-out'); setTimeout(() => el.remove(), 300); };
    x.onclick = close; el.append(x);
    if (el.getAttribute('role') === 'status') setTimeout(close, 5000);
  });
}
