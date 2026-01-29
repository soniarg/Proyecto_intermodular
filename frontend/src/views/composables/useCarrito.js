import { ref, computed, watch } from 'vue';

// Se sacan estas variables fuera para que al ir cambiando entre vistas, 
// no se pierda el carrito, y se mantenga en todas las vistas

const carrito = ref([]);

// Esta variable contiene el texto de 'carrito' debido a que se usa en varias
// funciones, lo que mejora la escabilidad
const claveLocalStorage = 'carrito';

export function useCarrito(){
    // El localStorage es un almacenamiento del navegador del usuario. En él
    // se guardan datos como el carrito, de manera que cada vez que se recargue la página
    // no se pierda el carrito y se mantengan los datos.

    // Aquí se comprueba que si existe una clave 'carrito' (se crea más adelante)
    // entonces significa que había un carrito y se guardan en el carrito todos
    // los juegos que había antes
    if(localStorage.getItem(claveLocalStorage)){
        carrito.value = JSON.parse(localStorage.getItem(claveLocalStorage));
    }

    const buscarJuegoCarrito = (juego) => carrito.value.find(item => item.id === juego.id);

    // Función para añadir un juego al carrito, se comprueba primero si el 
    // juego se había añadido ya o no al carrito
    const agregar = (juego) => {
        const juegoCarrito = buscarJuegoCarrito(juego);

        // Si el juego ya se había añadido, se actualiza su cantidad, para indicar
        // cuantas unidades de este producto se han añadido
        if(juegoCarrito){
            juegoCarrito.cantidad++;
        }else{
            // En caso de no haberse añadido aún, se crea una variable con todos
            // los datos del juego (esto es gracias a los tres puntos '...' 
            // delante de la variable con el juego, esto permite crear una copia
            // de los datos del objeto) y añadimos una propiedad de cantidad
            const nuevoJuego = {...juego, cantidad: 1};

            // Por último se añade el juego al carrito
            carrito.value.push(nuevoJuego);
        }
    }

    // Esta función permite restarle cantidad a un juego del carrito o
    // quitar un juego del carrito
    const quitar = (juego) => {
        const juegoCarrito = buscarJuegoCarrito(juego);

        // Importante comprobar si el juego está o no en el carrito, para que en 
        // caso de que no esté a la hora de ejecutar la función, hacer un retorno
        // limpio. En caso de no estar esta comprobación, se podría ejecutar un error
        // y quedarse colgada la web
        if(!juegoCarrito){
            return;
        }

        // En caso de que el juego tenga más de una unidad en el carrito, se le quita una
        if(juegoCarrito.cantidad > 1){
            juegoCarrito.cantidad--;
        
        // En caso contrario, se hace que el carrito tenga como valor todos los juegos,
        // exceptuando el juego con el mismo id por el que se está filtrando (es decir
        // quitar el juego del carrito)    
        }else{
            carrito.value = carrito.value.filter(item => item.id !== juego.id);
        }
    }

    const eliminar = (juego) => {
        const juegoCarrito = buscarJuegoCarrito(juego);

        if(!juegoCarrito){
            return;
        }

        carrito.value = carrito.value.filter(item => item.id !== juego.id);
    }

    const eliminarTodo = () => {
        carrito.value = [];
    }


    // Esta función permite encontrar un juego en el carrito, lo que ayuda
    // al componente hijo para definir la clase dinámica
    const buscar = (juegoId) => {
        return carrito.value.some(item => item.id === juegoId);
    }

    // Esta función como el nombre indica, devuelve la cantidad de veces
    // que se ha añadido un juego al carrito, para poder mostrar dicha cantidad
    // por pantalla a través del hijo
    const obtenerCantidad = (juego) => {
        const juegoCarrito = carrito.value.find(item => item.id === juego.id);

        return juegoCarrito ? juegoCarrito.cantidad : 0;
    }

    // Esta función permite mostrar el total del coste de los juegos del carrito
    const totalCarrito = computed(() => {
        // Función para guardar el total del coste del carrito. Es muy importante
        // crearla dentro de la función y no fuera, ya que entonces, la variable
        // no se reiniciaría cada vez que se guarde un juego o se quite uno, por lo
        // que mantendría el total previo al cambio y le sumaría el nuevo total, mostrando
        // un valor irreal
        let total = 0;

        // Si no hay ningún producto en el carrito, el total es 0
        if(carrito.value.length === 0){
            return 0;
        }

        // Se recorre cada juego del carrito para hacer la suma y calcular el total
        carrito.value.forEach(juego => {
            total += juego.cost * juego.cantidad
        });

        return total;
    });

    // La función watch accede al carrito internamente, por lo que al definir un
    // parámetro, podemos acceder al carrito sin poner .value . Si no se usara
    // dicho parametro, todas las veces que se ponga carrito tendría que 
    // ponerse también .value

    // La función watch permite vigilar una variable, y detectar un cambio para
    // ejecutar una función. En este caso, se vigila el carrito, de manera que
    // cuando se añade o se quita un producto, se guarda el nuevo carrito en el
    // localStorage.
    watch(carrito, (carritoLimpio) => {
        // Se convierte a JSON todo el array de juegos
        const carritoJson = JSON.stringify(carritoLimpio);

        // Se guarda en el localStorage, el JSON del carrito con la clave 'carrito'
        // (claveLocalStorage)
        localStorage.setItem(claveLocalStorage, carritoJson);
    }, {deep: true});
    // Por defecto, watch vigila una lista de manera superficial. Detecta cambios
    // cuando se añade o quita un objeto, pero no detecta cambios si un objeto es editado
    // por eso, se añade deep: true, para así poder guardar en el localStorage el carrito,
    // luego de sumarle o restarle a la cantidad de un juego

    return{
        carrito,
        totalCarrito,
        agregar,
        quitar,
        eliminar,
        eliminarTodo,
        buscar,
        obtenerCantidad
    }

}