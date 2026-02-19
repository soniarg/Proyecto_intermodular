# ProxiMarkt - Projecte Intermodular 2DAW

## 1. Descripció General del Projecte
ProxiMarkt és una plataforma de mercat de proximitat intel·ligent desenvolupada sota la metodologia d'Aprenentatge Basat en Projectes (ABP). L'objectiu principal és connectar productors i comerços locals amb consumidors finals, fomentant el comerç de Km 0, la sostenibilitat i la transparència en els preus.

La plataforma permet la geolocalització de productes, la gestió de reserves i la comunicació directa entre els actors implicats sense intermediaris logístics.

---

## 2. Fase d'Anàlisi i Validació de Negoci (IPE II / SASP)
Abans del desenvolupament tècnic, es va realitzar una fase d'estudi del mercat i viabilitat de la idea emprenedora.

### Anàlisi de l'Entorn
* **PESTEL i DAFO:** Es van identificar els factors polítics, econòmics, socials, tecnològics, ecològics i legals. L'anàlisi DAFO va permetre detectar la fortalesa de la tendència cap al consum sostenible i l'oportunitat de digitalitzar un sector tradicionalment analògic.
* **Sostenibilitat Aplicada (SASP):** El model de negoci s'alinea amb l'economia circular i el bé comú, reduint el desplaçament de mercaderies de llarga distància i minimitzant l'ús d'embalatges.

### Viabilitat Econòmica i Jurídica
* Es va analitzar la rendibilitat del projecte avaluant inversions inicials, costos operatius i beneficis.
* Es van comparar diferents formes jurídiques, concloent que la Societat Limitada (S.L.) és la més adequada per a l'escalabilitat del projecte.

### Imatge Corporativa (DIW)
Es va dissenyar una identitat coherent per transmetre els valors de la marca:
* **Logotip i Tipografia:** Dissenyats per evocar naturalitat i proximitat.
* **Paleta de colors:** Ús del Verd Esmeralda (#27ae60) com a eix central per simbolitzar sostenibilitat i productes frescos.

---

## 3. Stack Tecnològic

El projecte implementa una arquitectura desacoplada per garantir l'eficiència i la mantenibilitat dels mòduls DWEC i DWES.



### Frontend (DWEC / DIW)
* **Vue.js 3:** Utilització de la Composition API per a una gestió reactiva de la interfície d'usuari.
* **Vite:** Eina de construcció que optimitza el flux de treball i la compilació de recursos en temps real.
* **Axios:** Gestió de peticions asíncrones a l'API REST.
* **Disseny Responsive:** Interfície adaptada a dispositius mòbils per facilitar l'ús en mercats i entorns rurals.

### Backend (DWES)
* **Laravel:** Framework de PHP per al desenvolupament de la lògica de servidor i la API REST.
* **Eloquent ORM:** Mapeig d'objectes relacionales que permet una interacció fluida amb la base de dades sense necessitat de codi SQL manual.
* **Laravel Sanctum:** Autenticació segura mitjançant tokens per a la protecció de dades d'usuaris i comandes.

### Base de Dades
* **MySQL:** Emmagatzematge relacional per a la persistència de dades de productes, usuaris, reserves i missatgeria.

---

## 4. Fase de Desenvolupament (Metodologia SCRUM)
El projecte s'ha executat en 8 Sprints setmanals, integrant les competències de Digitalització Aplicada (DASP) i la gestió del temps.



### Sprints de Desenvolupament
1. **Sprint 1 - Prototipat:** Disseny de la interfície (UI) i creació de l'esquema inicial de la base de dades.
2. **Sprint 2 - Gestió d'Usuaris:** Implementació del login, registre i integració de mapes per a la geolocalització.
3. **Sprint 3 - Gestió de Vendes:** Funcionalitats per a l'agricultor (publicació de productes, fotos i preus).
4. **Sprint 4 - Gestió de Comandes:** Interfície per al comprador (reserva de productes i selecció de punts d'entrega).
5. **Sprint 5 - Filtres i Cerca:** Desenvolupament del cercador per categoria, preu i ciutat per millorar l'experiència d'usuari (UX).
6. **Sprint 6 - Comunicacions:** Implementació del xat integrat entre comprador i venedor i sistema de valoracions post-venda.

---

## 5. Fase de Desplegament i Seguretat (DAW)
Corresponent a l'Sprint 7, es va procedir a la posada en marxa en un entorn de producció real.

### Infraestructura en Microsoft Azure
El desplegament s'ha realitzat utilitzant els següents serveis de núvol:
* **Azure Static Web Apps:** Per a l'allotjament del frontend de Vue.js amb distribució global.
* **Azure App Service:** Per a l'execució del backend de Laravel sota un entorn segur.
* **Azure Database for MySQL:** Servidor gestionat per a la base de dades relacional.
* **Azure Storage:** Emmagatzematge d'imatges de productes i avatars de perfil.



### Seguretat i Protocol
* **HTTPS:** Implementació de certificats SSL per garantir la xifratge de les dades en trànsit.
* **Control de Sessions:** Gestió estricte de sessions d'usuari per evitar l'accés no autoritzat a les dades de contacte i reserves.

---

## 6. Competències Personals i Socials
Durant tot el procés, l'equip ha aplicat estratègies de:
* **Treball en Equip:** Presa de decisions conjunta en les reunions de Sprint Planning i Daily Scrums.
* **Gestió del Temps:** Compliment dels terminis de lliurament fixats per al projecte intermodular (240 hores).
* **Resolució de Conflictes:** Capacitat d'adaptació davant problemes tècnics o canvis en els requisits del Product Owner.

---

## 7. Producte Final i Entrega (Sprint 8)
El resultat final és una aplicació funcional accessible públicament que compleix amb:
1. API REST robusta (DWES).
2. Interfície dinàmica i reactiva (DWEC).
3. Disseny adaptat a la imatge corporativa (DIW).
4. Desplegament segur en el núvol (DAW).

Projecte realitzat pel Grup: Pablo, Sonia, Manu e Iván.