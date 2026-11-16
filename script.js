(() => {
 'use strict';
 const $ = (selector) => document.querySelector(selector);
 const navToggle = $('.nav-toggle');
 const mobileMenu = $('#mobile-menu');
 navToggle?.addEventListener('click', () => {
   const open = navToggle.getAttribute('aria-expanded') === 'true';
   navToggle.setAttribute('aria-expanded', String(!open));
   navToggle.setAttribute('aria-label', open ? 'Abrir menú' : 'Cerrar menú');
   mobileMenu.hidden = open;
 });
 mobileMenu?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
   mobileMenu.hidden = true;
   navToggle.setAttribute('aria-expanded', 'false');
   navToggle.setAttribute('aria-label', 'Abrir menú');
 }));
 $('#year').textContent = new Date().getFullYear();
 const medio = $('#medio'), email = $('#email'), telefono = $('#telefono');
 function syncPreferredContact() {
   email.required = medio.value === 'Email';
   telefono.required = medio.value === 'WhatsApp';
   $('#email-required').hidden = !email.required;
   $('#phone-required').hidden = !telefono.required;
 }
 medio.addEventListener('change', syncPreferredContact);
 syncPreferredContact();
 const designOptions = document.querySelectorAll('input[name="diseno"]');
 const uploadPanel = $('#upload-panel');
 const designRequest = $('#design-request-note');
 const uploadInputs = [$('#archivo_tapa'), $('#archivo_heladerita')];
 function syncDesign() {
   const choice = document.querySelector('input[name="diseno"]:checked')?.value;
   uploadPanel.hidden = choice !== 'Adjunto mis diseños';
   designRequest.hidden = choice !== 'Solicito diseño';
   uploadInputs.forEach(input => {input.disabled = choice !== 'Adjunto mis diseños';});
 }
 designOptions.forEach(input => input.addEventListener('change', syncDesign));
 syncDesign();
 const dateInput = $('#fecha');
 function getMinimumDate(){const d=new Date();d.setHours(12,0,0,0);d.setDate(d.getDate()+5);const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),day=String(d.getDate()).padStart(2,'0');return `${y}-${m}-${day}`;}
 dateInput.min = getMinimumDate();
 const form = $('#party-form'), msg = $('#form-message');
 const submit = form.querySelector('button[type=submit]');
 function showMsg(text,status){msg.textContent=text;msg.dataset.status=status;msg.hidden=false;msg.scrollIntoView({behavior:'smooth',block:'nearest'});}
 form.addEventListener('submit',async event => {
   event.preventDefault(); msg.hidden = true;
   if (!form.checkValidity()) {form.reportValidity();return;}
   const maxSize = 10 * 1024 * 1024;
   for(const input of uploadInputs){if(input.files?.[0] && input.files[0].size>maxSize){showMsg(`El archivo de ${input === uploadInputs[0] ? 'la tapa' : 'la heladerita'} supera los 10 MB.`, 'error');input.focus();return;}}
   // El archivo puede verse en modo local sin enviar datos.
   if (window.location.protocol === 'file:') {showMsg('Vista previa: el formulario está completo y validado, pero la consulta no se envió. Para recibir pedidos reales hay que publicarlo en un hosting con PHP y configurar el correo en enviar.php.','demo');return;}
   try {
     submit.disabled = true;submit.textContent = 'Enviando consulta…';
     const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});
     const payload=await response.json();
     if(!response.ok||!payload.ok) throw new Error(payload.message||'No pudimos enviar la consulta. Intentá nuevamente.');
     form.reset();syncPreferredContact();syncDesign();showMsg('¡Tu consulta fue enviada! Nos comunicaremos por el medio que elegiste.','success');
   }catch(e){showMsg(e.message || 'Hubo un problema al enviar. Por favor, intentá nuevamente.','error');}
   finally{submit.disabled=false;submit.innerHTML='Enviar mi consulta <span aria-hidden="true">↗</span>';}
 });
})();
