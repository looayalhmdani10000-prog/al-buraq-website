// Simple JS: mobile nav toggle and form handling
document.addEventListener('DOMContentLoaded',function(){
  // Mobile nav toggle
  document.querySelectorAll('.nav-toggle').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const nav = btn.nextElementSibling || document.getElementById('siteNav')
      if(nav) nav.style.display = (nav.style.display==='flex' || nav.style.display==='block')? 'none' : 'block'
    })
  })

  // Contact form (contact.html)
  const contactForm = document.getElementById('contact-form')
  if(contactForm){
    contactForm.addEventListener('submit', function(e){
      e.preventDefault();
      document.getElementById('contactResult').textContent = 'Thanks! We will reply shortly.'
      contactForm.reset()
    })
  }

  // Booking form (booking.html) — client-side validation + AJAX submit (progressive enhancement)
  const bookingForm = document.getElementById('booking-form')
  if(bookingForm){
    bookingForm.addEventListener('submit', async function(e){
      e.preventDefault();
      const resultEl = document.getElementById('bookingResult');
      resultEl.style.color = '';

      const form = bookingForm;
      const fd = new FormData(form);
      const required = ['name','email','phone','pickup_date','pickup_location','dropoff_location','weight'];
      const missing = [];
      required.forEach(k=>{ if(!fd.get(k) || fd.get(k).toString().trim()==='') missing.push(k) });
      const email = fd.get('email') ? fd.get('email').toString().trim() : '';
      const emailRe = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      if (missing.length) {
        resultEl.style.color = 'crimson';
        resultEl.textContent = 'الرجاء تعبئة الحقول المطلوبة: ' + missing.join(', ');
        return;
      }
      if (!emailRe.test(email)) {
        resultEl.style.color = 'crimson';
        resultEl.textContent = 'الرجاء إدخال بريد إلكتروني صالح.';
        return;
      }

      // Add retry logic for transient server errors
      const maxAttempts = 3;
      let attempt = 0;
      while (attempt < maxAttempts) {
        attempt++;
        try {
          resultEl.style.color = '';
          resultEl.textContent = (attempt > 1 ? `إعادة المحاولة... (${attempt}/${maxAttempts})` : 'جاري الإرسال...');
          const res = await fetch(form.action, { method: form.method, body: fd, headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'} });
          const contentType = res.headers.get('content-type') || '';

          if (res.ok) {
            // Try JSON first for AJAX success
            if (contentType.indexOf('application/json') !== -1) {
              const json = await res.json();
              resultEl.style.color = 'green';
              resultEl.textContent = json.message || 'تم إرسال الحجز بنجاح';
              form.reset();
              break;
            } else {
              const text = await res.text();
              resultEl.style.color = 'green';
              resultEl.textContent = text || 'تم إرسال الحجز بنجاح';
              form.reset();
              break;
            }

          } else if (res.status === 400) {
            // Validation errors - prefer JSON if provided
            if (contentType.indexOf('application/json') !== -1) {
              const json = await res.json();
              const errs = json.errors || [];
              const arab = {name:'الاسم', email:'البريد الإلكتروني', phone:'الهاتف', pickup_date:'تاريخ الاستلام', pickup_location:'مكان الاستلام', dropoff_location:'مكان التسليم', weight:'الوزن', email_invalid:'البريد الإلكتروني غير صالح'};
              const mapped = errs.map(e => arab[e] || e);
              resultEl.style.color = 'crimson';
              resultEl.textContent = 'الحقول المفقودة أو غير الصالحة: ' + mapped.join(', ');
            } else {
              const text = await res.text();
              if (text.indexOf('Invalid submission') !== -1) {
                const m = text.match(/Missing or invalid fields: ([\s\S]*?)<\/p>/);
                resultEl.style.color = 'crimson';
                resultEl.textContent = m ? 'الحقول المفقودة أو غير الصالحة: ' + m[1].trim() : 'الرجاء تصحيح الحقول وإعادة الإرسال.';
              } else {
                resultEl.style.color = 'crimson';
                resultEl.textContent = 'الرجاء تصحيح الحقول وإعادة الإرسال.';
              }
            }
            break; // don't retry validation errors
          } else if (res.status >= 500) {
            // transient server error — retry up to maxAttempts
            if (attempt < maxAttempts) {
              await new Promise(r => setTimeout(r, 800 * attempt));
              continue;
            }
            resultEl.style.color = 'crimson';
            resultEl.textContent = 'حدث خطأ في الخادم. الرجاء المحاولة لاحقًا.';
            break;
          } else {
            resultEl.style.color = 'crimson';
            resultEl.textContent = 'تعذر حفظ الحجز. الرجاء المحاولة لاحقًا.';
            break;
          }
        } catch (err) {
          // Network error — retry
          if (attempt < maxAttempts) {
            await new Promise(r => setTimeout(r, 800 * attempt));
            continue;
          }
          resultEl.style.color = 'crimson';
          resultEl.textContent = 'خطأ في الاتصال. الرجاء التحقق من الاتصال والمحاولة مرة أخرى.';
          break;
        }
      }
    });
  }
})