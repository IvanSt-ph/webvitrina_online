import './bootstrap'

document.addEventListener('click', (e)=>{
  const btn = e.target.closest('form[action*="/cart/add/"] button');
  if(!btn) return;
  const dot = document.createElement('span');
  dot.className='fixed w-3 h-3 rounded-full bg-indigo-600 z-[60] pointer-events-none';
  const rect = btn.getBoundingClientRect();
  dot.style.left = rect.left+rect.width/2+'px';
  dot.style.top = rect.top+'px';
  document.body.appendChild(dot);
  const cart = document.querySelector('a[href="/cart"]');
  const end = cart?.getBoundingClientRect();
  if(end){
    dot.animate([
      {transform:'translate(0,0)', opacity:1},
      {transform:`translate(${end.left-rect.left}px, ${end.top-rect.top}px) scale(0.3)`, opacity:0.1}
    ],{duration:600, easing:'cubic-bezier(.17,.67,.83,.67)'}).onfinish=()=>dot.remove();
  } else { setTimeout(()=>dot.remove(), 600); }
});


import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
