<script setup>
import { ref } from 'vue';

const email = ref('');
const password = ref('');

const iniciarSesion = () => {
    const datos = {
        email: email.value,
        password: password.value
    };
    console.log("Datos: Email-> ", email.value , "Contraseña-> ", password.value)
};
</script>

<template>
    <h1>Inicio de sesion</h1>
    <fieldset>
        <!-- En lugar de usar el action, queremos enviar los datos
         del formulario usando Vue, y además queremos evitar que la página
         se recargue sola. De ahí, usamos @submit más la propiedad prevent
         y dentro, definimos la función la cuál se encargará de enviar los datos -->
        <form @submit.prevent="iniciarSesion">
            <label for="email">Email</label><br>
            <input 
            type="email" 
            id="email" 
            name="email" 
            v-model="email"
            placeholder="ejemplo@gmail.com"
            required
            ><br><br>

            <label for="password">Contraseña</label><br>
            <input 
            type="password"
            id="password"
            name="password"
            v-model="password"
            placeholder="••••••••"
            required><br><br>

            <button type="submit">Enviar</button>
        </form>
    </fieldset>
</template>