document.addEventListener('DOMContentLoaded', () => {
  if (window.AOS) {
    AOS.init({
      duration: 800,
      once: true,
      offset: 90
    });
  }

  const header = document.querySelector('header');
  const hamburger = document.getElementById('hamburger');
  const navMenu = document.getElementById('menu');
  const navLinks = document.querySelectorAll('.nav-links a');
  const sections = document.querySelectorAll('main section[id]');
  const scrollTopBtn = document.getElementById('scrollTop');
  const contactForm = document.getElementById('contactForm');
  const formMessage = document.getElementById('formMessage');

  if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
      const isActive = navMenu.classList.toggle('active');
      hamburger.setAttribute('aria-expanded', String(isActive));
    });
  }

  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      if (navMenu) {
        navMenu.classList.remove('active');
      }
      if (hamburger) {
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });
  });

  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (event) => {
      const targetId = anchor.getAttribute('href');
      if (!targetId || targetId === '#') {
        event.preventDefault();
        return;
      }

      const target = document.querySelector(targetId);
      if (!target) {
        return;
      }

      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  const updateOnScroll = () => {
    const scrollPosition = window.scrollY + 180;

    sections.forEach((section) => {
      const id = section.getAttribute('id');
      const link = document.querySelector(`.nav-links a[href="#${id}"]`);
      if (!link) {
        return;
      }

      const isCurrent = scrollPosition >= section.offsetTop && scrollPosition < section.offsetTop + section.offsetHeight;
      link.classList.toggle('active', isCurrent);
    });

    if (header) {
      header.style.boxShadow = window.scrollY > 40
        ? '0 18px 40px rgba(11, 61, 120, 0.12)'
        : '0 10px 30px rgba(11, 61, 120, 0.08)';
    }

    if (scrollTopBtn) {
      scrollTopBtn.classList.toggle('show', window.scrollY > 320);
    }
  };

  window.addEventListener('scroll', updateOnScroll);
  updateOnScroll();

  if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  const showMessage = (message, type) => {
    if (!formMessage) {
      return;
    }

    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
  };

  if (contactForm) {
    contactForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      const nombre = document.getElementById('nombre')?.value.trim() || '';
      const correo = document.getElementById('correo')?.value.trim() || '';
      const telefono = document.getElementById('telefono')?.value.trim() || '';
      const politicas = document.getElementById('politicas')?.checked;
      const submitBtn = contactForm.querySelector('.btn-submit');
      const originalHtml = submitBtn ? submitBtn.innerHTML : '';

      if (!nombre) {
        showMessage('Por favor, ingresa tu nombre completo.', 'error');
        return;
      }

      if (!validateEmail(correo)) {
        showMessage('Por favor, ingresa un correo electrónico válido.', 'error');
        return;
      }

      if (!telefono) {
        showMessage('Por favor, ingresa tu número de teléfono.', 'error');
        return;
      }

      if (!politicas) {
        showMessage('Debes aceptar el tratamiento de datos para continuar.', 'error');
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Enviando...</span>';
      }

      showMessage('Estamos enviando tu solicitud...', 'success');

      try {
        const response = await fetch(contactForm.action, {
          method: 'POST',
          body: new FormData(contactForm),
          headers: {
            Accept: 'application/json'
          }
        });

        const raw = await response.text();
        let result;

        try {
          result = JSON.parse(raw);
        } catch (error) {
          throw new Error('La respuesta del servidor no fue JSON válido.');
        }

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'No fue posible enviar el formulario.');
        }

        showMessage(result.message, 'success');
        contactForm.reset();

        if (window.Swal) {
          Swal.fire({
            title: 'Mensaje enviado',
            text: result.message,
            icon: 'success',
            confirmButtonColor: '#f97316'
          });
        }
      } catch (error) {
        showMessage(error.message || 'Ocurrió un problema al enviar el formulario.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalHtml;
        }
      }
    });
  }
});
