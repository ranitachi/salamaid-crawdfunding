import './bootstrap';

const slides=[...document.querySelectorAll('.hero-card')],dots=[...document.querySelectorAll('.dots button')];let currentSlide=0;
function showSlide(index){currentSlide=index;slides.forEach((slide,i)=>slide.classList.toggle('active',i===index));dots.forEach((dot,i)=>dot.classList.toggle('active',i===index))}
dots.forEach((dot,index)=>dot.addEventListener('click',()=>showSlide(index)));
setInterval(()=>showSlide((currentSlide+1)%slides.length),5000);
document.querySelector('.modal-close')?.addEventListener('click',()=>document.querySelector('#campaign-modal')?.classList.add('hidden'));
document.querySelector('#campaign-modal')?.addEventListener('click',event=>{if(event.target.id==='campaign-modal')event.currentTarget.classList.add('hidden')});
document.querySelector('#campaign-search')?.addEventListener('input',event=>{const query=event.target.value.toLowerCase().trim();document.querySelectorAll('.program-card').forEach(card=>{card.style.display=!query||card.innerText.toLowerCase().includes(query)?'':'none'})});
document.querySelectorAll('.category').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('.category').forEach(item=>item.classList.remove('selected'));button.classList.add('selected');const category=button.dataset.category;document.querySelectorAll('.program-card').forEach(card=>{card.style.display=category==='Semua'||card.dataset.category===category?'':'none'})}));
