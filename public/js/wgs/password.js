export default function password() {
  document.querySelectorAll('input[type=password]').forEach(i => {
    const w = document.createElement('div'); w.className = 'wgs-pw';
    i.parentNode.insertBefore(w, i); w.append(i);
    const b = document.createElement('button'); b.type = 'button'; b.textContent = 'Lihat';
    b.onclick = () => { const s = i.type === 'password'; i.type = s ? 'text' : 'password'; b.textContent = s ? 'Tutup' : 'Lihat'; };
    w.append(b);
  });
}
