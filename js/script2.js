javascript
// ========== CARRUSEL DE IMÁGENES ==========
document.addEventListener('DOMContentLoaded', function() {
    
    // ===== CONFIGURACIÓN DEL CARRUSEL =====
    // LISTA DE TUS IMÁGENES (ajusta los nombres según tus archivos)
    const imagenes = [
        'demo.jpg',
        'camion_peru2.jpg',
        'camion_peru3.jpg',
        'camion_peru4.jpg',
        'camion_peru5.jpg',
        'camion_peru6.jpg',
        'camion_peru7.jpg',
        'camion_peru8.jpg',
        'camion_peru9.jpg',
        'camion_peru10.jpg',
        'camion_peru11.jpg',
        'camion_peru12.jpg',
        'camion_peru13.jpg',
        'camion_peru14.jpg'
    ];
    
    // Ruta base de las imágenes
    const rutaBase = 'assets/img/camiones/';
    
    let indiceActual = 0;
    let intervaloAuto;
    const tiempoCambio = 4000; // 4 segundos entre cambios
    
    const imgElement = document.getElementById('carouselImage');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const dotsContainer = document.getElementById('carouselDots');
    
    // Función para actualizar la imagen mostrada
    function actualizarImagen() {
        if (imgElement) {
            imgElement.src = rutaBase + imagenes[indiceActual];
            imgElement.alt = `Camión ${indiceActual + 1}`;
            actualizarDots();
        }
    }
    
    // Crear los puntos indicadores
    function crearDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        imagenes.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (index === indiceActual) dot.classList.add('active');
            dot.addEventListener('click', () => {
                detenerAuto();
                indiceActual = index;
                actualizarImagen();
                iniciarAuto();
            });
            dotsContainer.appendChild(dot);
        });
    }
    
    // Actualizar el estado de los dots
    function actualizarDots() {
        const dots = document.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            if (index === indiceActual) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    // Siguiente imagen
    function siguienteImagen() {
        indiceActual = (indiceActual + 1) % imagenes.length;
        actualizarImagen();
    }
    
    // Imagen anterior
    function anteriorImagen() {
        indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
        actualizarImagen();
    }
    
    // Iniciar reproducción automática
    function iniciarAuto() {
        if (intervaloAuto) clearInterval(intervaloAuto);
        intervaloAuto = setInterval(siguienteImagen, tiempoCambio);
    }
    
    // Detener reproducción automática
    function detenerAuto() {
        if (intervaloAuto) {
            clearInterval(intervaloAuto);
            intervaloAuto = null;
        }
    }
    
    // Eventos de los botones
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            detenerAuto();
            anteriorImagen();
            iniciarAuto();
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            detenerAuto();
            siguienteImagen();
            iniciarAuto();
        });
    }
    
    // Pausar carrusel al hacer hover
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', detenerAuto);
        carouselContainer.addEventListener('mouseleave', iniciarAuto);
    }
    
    // Inicializar carrusel
    if (imagenes.length > 0 && imgElement) {
        crearDots();
        actualizarImagen();
        iniciarAuto();
    }
    
});