<script setup>
// Footer estático
</script>

<template>
  <footer class="main-footer">
    <div class="footer-container">
      
      <div class="footer-col brand-col">
        <h2 class="footer-logo">Proxi<span class="highlight">Markt</span></h2>
        <p class="footer-desc">
          Conectando agricultores locales con vecinos. 
          Productos frescos, sin intermediarios.
        </p>
      </div>

      <div class="footer-col">
        <h3>Explorar</h3>
        <ul>
          <li><router-link to="/">Inicio</router-link></li>
          <li><router-link to="/marketplace">Marketplace</router-link></li>
          <li><router-link to="/mapas">Mapa Local</router-link></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Tu Cuenta</h3>
        <ul>
          <li><router-link to="/login">Entrar</router-link></li>
          <li><router-link to="/register">Registrarse</router-link></li>
        </ul>
      </div>

      <div class="footer-col contact-col">
        <h3>Síguenos</h3>
        
        <div class="social-links">
            <a href="https://instagram.com" target="_blank" class="social-item instagram">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="icon"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                <span>@proximarkt</span>
            </a>

            <a href="https://tiktok.com" target="_blank" class="social-item tiktok">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path></svg>
                <span>@proximarkt</span>
            </a>
        </div>

        <div class="contact-info">
            <p>📩 proximarkt@gmail.com</p>
        </div>
      </div>

    </div>

    <div class="footer-bottom">
      <p>&copy; {{ new Date().getFullYear() }} ProxiMarkt. Todos los derechos reservados.</p>
        <a id="terminos" href="/terminos.pdf" target="_blank" rel="noopener noreferrer">
            Términos y Condiciones (PDF)</a>
    </div>
  </footer>
</template>

<style scoped>
/* --- ESTRUCTURA PRINCIPAL --- */
.main-footer {
  background-color: #1e293b; /* Fondo oscuro */
  color: #94a3b8; /* Texto gris */
  padding: 50px 20px 20px 20px;
  margin-top: auto;
  border-top: 1px solid #334155;
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 40px;
  padding-bottom: 40px;
}

.footer-col {
  flex: 1;
  min-width: 200px;
}

.brand-col { max-width: 300px; }

/* TÍTULOS */
h3 {
  color: white;
  font-size: 1.1rem;
  margin-bottom: 20px;
  font-weight: 600;
}

.footer-logo { color: white; margin-bottom: 10px; font-size: 1.6rem; font-weight: 800; }
.highlight { color: #10b981; }

/* LISTAS DE ENLACES */
ul { list-style: none; padding: 0; }
ul li { margin-bottom: 10px; }
ul li a { color: #94a3b8; text-decoration: none; transition: 0.2s; }
ul li a:hover { color: #10b981; padding-left: 5px; }

/* --- REDES SOCIALES (NUEVO) --- */
.social-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.social-item {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #cbd5e1; /* Texto más claro */
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    width: fit-content;
}

.icon {
    width: 20px;
    height: 20px;
}

/* Colores al pasar el ratón */
.social-item:hover {
    transform: translateX(5px);
}

.social-item.instagram:hover { color: #E1306C; } /* Rosa Instagram */
.social-item.tiktok:hover { color: #E1306C; } /* Efecto TikTok */

.contact-info p {
    margin: 5px 0;
    font-size: 0.9rem;
}

/* --- COPYRIGHT --- */
.footer-bottom {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #334155;
  font-size: 0.85rem;
  color: #64748b;
}

#terminos {
    color: white;
}

/* MÓVIL */
@media (max-width: 768px) {
  .footer-container { flex-direction: column; gap: 30px; }
  .footer-logo { font-size: 1.4rem; }
}
</style>