import { useState } from 'react'
import reactLogo from './assets/react.svg'
import viteLogo from './assets/vite.svg'
import heroImg from './assets/hero.png'
import './App.css'

function MailIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M3 5.25h18A1.75 1.75 0 0 1 22.75 7v10A1.75 1.75 0 0 1 21 18.75H3A1.75 1.75 0 0 1 1.25 17V7A1.75 1.75 0 0 1 3 5.25Zm0 1.5a.25.25 0 0 0-.25.25v.2l9.25 6.01 9.25-6.01V7a.25.25 0 0 0-.25-.25H3Zm18 10.5a.25.25 0 0 0 .25-.25V8.98l-8.84 5.74a.75.75 0 0 1-.82 0L2.75 8.98V17c0 .14.11.25.25.25h18Z" />
    </svg>
  )
}

function PhoneIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M6.7 2.75h2.1c.46 0 .86.32.97.76l.76 3.02a1 1 0 0 1-.28.96L8.8 8.94a14.47 14.47 0 0 0 6.25 6.25l1.45-1.45a1 1 0 0 1 .96-.28l3.02.76c.44.11.76.51.76.97v2.1A1.96 1.96 0 0 1 19.29 19C10.56 19 5 13.44 5 6.71c0-1.08.62-1.96 1.7-1.96Z" />
    </svg>
  )
}

function App() {
  const [count, setCount] = useState(0)

  return (
    <>
      <div className="top-bar">
        <div className="contact-info">
          <a href="mailto:gerencia@peruaduanas.com">
            <MailIcon />
            <span>gerencia@peruaduanas.com</span>
          </a>
          <a href="tel:+51958954165">
            <PhoneIcon />
            <span>+51 958954165</span>
          </a>
        </div>
      </div>

      <main className="page-shell">
        <section id="center" className="hero-section">
          <div className="hero-copy">
            <p className="eyebrow">Logistica y transporte con presencia nacional</p>
            <h1>PeruTranspor mueve tu carga con precision y confianza</h1>
            <h2>
              Una demo en React para presentar contacto directo, identidad visual y una
              base moderna lista para crecer.
            </h2>
            <p className="support-copy">
              Esta version inicial conserva una experiencia simple: hero principal,
              accesos de contacto y un contador interactivo para validar el flujo de
              React y el render con Vite.
            </p>
            <button
              className="counter"
              onClick={() => setCount((currentCount) => currentCount + 1)}
            >
              Numero de veces presionado: {count}
            </button>
          </div>

          <div className="hero-visual">
            <div className="hero-badge">React + Vite Demo</div>
            <div className="hero-art">
              <img
                src={heroImg}
                className="base"
                width="360"
                height="360"
                alt="Ilustracion de transporte y distribucion para PeruTranspor"
              />
              <img src={reactLogo} className="framework" alt="React logo" />
              <img src={viteLogo} className="vite" alt="Vite logo" />
            </div>
          </div>
        </section>

        <div className="ticks" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
        </div>

        <section id="next-steps" className="next-steps">
          <article>
            <h3>Base visual lista</h3>
            <p>
              La app ya cuenta con top bar, hero, gradientes, CTA y una estructura
              clara para seguir construyendo secciones reales del negocio.
            </p>
          </article>
          <article>
            <h3>Escalable a sitio comercial</h3>
            <p>
              Desde aqui se puede incorporar formulario, carrusel, servicios y
              testimonios sin arrastrar la arquitectura vanilla anterior.
            </p>
          </article>
          <article>
            <h3>Preparada para despliegue</h3>
            <p>
              La demo usa una configuracion estandar de Vite, con scripts de desarrollo,
              compilacion y vista previa.
            </p>
          </article>
        </section>
      </main>
    </>
  )
}

export default App
